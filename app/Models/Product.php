<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string|null $sku
 * @property string|null $description
 * @property string|null $unit
 * @property string|null $category
 * @property string|null $brand
 * @property int $min_stock_level
 * @property int $critical_stock_level
 * @property int|null $yellow_alert_level
 * @property int|null $red_alert_level
 * @property bool $is_active
 * @property bool $has_expiration_date
 * @property int|null $clinic_id
 * @property bool $has_sub_unit
 * @property string|null $sub_unit_name
 * @property int|null $sub_unit_multiplier
 * @property bool $show_zero_stock_in_critical
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int $total_stock
 * @property-read int $batches_count
 * @property-read string $stock_status
 * @property-read float $total_stock_value
 * @property-read float $average_cost
 * @property-read float $potential_revenue
 * @property-read float $potential_profit
 * @property-read float $profit_margin
 * @property-read float $last_purchase_price
 * @property-read int $total_in
 * @property-read int $total_out
 * @property-read float|null $sale_price
 * @property-read \App\Models\Clinic|null $clinic
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stock[] $batches
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Product joinStockSummary(?int $clinicId = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|Product where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'sku', 'description', 'unit', 'category', 'brand',
        'min_stock_level', 'critical_stock_level',
        'yellow_alert_level', 'red_alert_level',
        'is_active', 'has_expiration_date', 'clinic_id',
        'has_sub_unit', 'sub_unit_name', 'sub_unit_multiplier',
        'show_zero_stock_in_critical',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_expiration_date' => 'boolean',
        'min_stock_level' => 'integer',
        'critical_stock_level' => 'integer',
        'yellow_alert_level' => 'integer',
        'red_alert_level' => 'integer',
        'clinic_id' => 'integer',
        'has_sub_unit' => 'boolean',
        'sub_unit_multiplier' => 'integer',
        'show_zero_stock_in_critical' => 'boolean',
    ];

    // Relationships
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Stock::class, 'product_id');
    }

    public function latestBatch(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_id')->latestOfMany('id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'product_id');
    }

    public function stockTransactions()
    {
        return $this->hasManyThrough(StockTransaction::class, Stock::class, 'product_id', 'stock_id');
    }

    // Accessors
    public function getTotalStockAttribute()
    {
        if (array_key_exists('total_stock', $this->attributes)) {
            return (int) $this->attributes['total_stock'];
        }

        if (! $this->relationLoaded('batches')) {
            $this->load('batches');
        }

        return $this->batches->sum(function ($batch) {
            if (! $batch->has_sub_unit) {
                return $batch->current_stock;
            }

            return ($batch->current_stock * ($batch->sub_unit_multiplier ?? 1)) + $batch->current_sub_stock;
        });
    }

    public function getStockStatusAttribute()
    {
        if (! $this->is_active) {
            return \App\Enums\StockStatus::INACTIVE->value;
        }

        $total = $this->total_stock;
        $redLevel = $this->red_alert_level ?? $this->critical_stock_level;
        $yellowLevel = $this->yellow_alert_level ?? $this->min_stock_level;
        $hideZeroFromCritical = ! ($this->show_zero_stock_in_critical ?? true);

        if ($total <= $redLevel && ! ($total === 0 && $hideZeroFromCritical)) {
            return 'critical';
        }
        if ($total <= $yellowLevel) {
            return \App\Enums\StockStatus::LOW_STOCK->value;
        }

        return 'normal';
    }

    // 🏦 Finansal Hesaplamalar (Sadece ilişkiler yüklüyse hesapla)
    public function getTotalStockValueAttribute()
    {
        if (! $this->relationLoaded('batches')) {
            return 0;
        }

        return $this->batches->sum(function ($batch) {
            $totalUnits = $batch->has_sub_unit
                ? ($batch->current_stock * ($batch->sub_unit_multiplier ?? 1)) + $batch->current_sub_stock
                : $batch->current_stock;

            return $totalUnits * ($batch->purchase_price ?? 0);
        });
    }

    public function getAverageCostAttribute()
    {
        $totalUnits = $this->total_stock;
        if ($totalUnits <= 0) {
            return 0;
        }

        return $this->total_stock_value / $totalUnits;
    }

    public function getPotentialRevenueAttribute()
    {
        return $this->total_stock * ($this->sale_price ?? 0);
    }

    public function getPotentialProfitAttribute()
    {
        $totalCost = $this->total_stock_value;
        $totalRevenue = $this->potential_revenue;

        return $totalRevenue - $totalCost;
    }

    public function getProfitMarginAttribute()
    {
        $totalRevenue = $this->potential_revenue;
        if ($totalRevenue <= 0) {
            return 0;
        }

        return ($this->potential_profit / $totalRevenue) * 100;
    }

    public function getLastPurchasePriceAttribute()
    {
        if ($this->relationLoaded('batches')) {
            $lastBatch = $this->batches
                ->where('current_stock', '>', 0)
                ->sortByDesc(function (Stock $batch) {
                    return sprintf(
                        '%s-%010d',
                        optional($batch->purchase_date)->format('Y-m-d H:i:s') ?? '0000-00-00 00:00:00',
                        $batch->id
                    );
                })
                ->first();

            return $lastBatch ? $lastBatch->purchase_price : 0;
        }

        // Aggregate using DB query if relation not loaded to prevent N+1 and memory exhaustion
        $lastBatch = Stock::where('product_id', $this->id)
            ->where('current_stock', '>', 0)
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->first(['purchase_price']);

        return $lastBatch ? $lastBatch->purchase_price : 0;
    }

    public function getTotalInAttribute()
    {
        if (array_key_exists('total_in', $this->attributes)) {
            return (int) $this->attributes['total_in'];
        }

        $stockIds = $this->getLoadedOrFreshBatches()->pluck('id');

        if ($stockIds->isEmpty()) {
            return 0;
        }

        return (int) StockTransaction::query()
            ->whereIn('stock_id', $stockIds)
            ->whereIn('type', self::incomingTransactionTypes())
            ->sum('quantity');
    }

    public function getTotalOutAttribute()
    {
        if (array_key_exists('total_out', $this->attributes)) {
            return (int) $this->attributes['total_out'];
        }

        $stockIds = $this->getLoadedOrFreshBatches()->pluck('id');

        if ($stockIds->isEmpty()) {
            return 0;
        }

        return (int) StockTransaction::query()
            ->whereIn('stock_id', $stockIds)
            ->whereIn('type', self::outgoingTransactionTypes())
            ->sum('quantity');
    }

    protected function getLoadedOrFreshBatches(): Collection
    {
        if ($this->relationLoaded('batches')) {
            return $this->batches;
        }

        return $this->batches()->get();
    }

    public static function incomingTransactionTypes(): array
    {
        return [
            'in',
            'entry',
            'purchase',
            'transfer_in',
            'returned',
            'return_in',
            'adjustment_in',
            'adjustment_plus',
            'adjustment_increase',
        ];
    }

    public static function outgoingTransactionTypes(): array
    {
        return [
            'out',
            'usage',
            'loss',
            'transfer_out',
            'expired',
            'damaged',
            'return_out',
            'adjustment_out',
            'adjustment_minus',
            'adjustment_decrease',
        ];
    }

    /**
     * Stok özet verilerini sorguya join eder.
     */
    public function scopeJoinStockSummary($query, ?int $clinicId = null)
    {
        $totalBaseUnitsSql = Stock::totalBaseUnitsRaw();

        $stockSummary = Stock::query()
            ->selectRaw('product_id')
            ->selectRaw("SUM(CASE WHEN is_active THEN {$totalBaseUnitsSql} ELSE 0 END) as total_stock")
            ->selectRaw('SUM(CASE WHEN is_active THEN 1 ELSE 0 END) as batches_count')
            ->whereNull('deleted_at');

        if ($clinicId) {
            $stockSummary->where('clinic_id', $clinicId);
        }

        $stockSummary->groupBy('product_id');

        return $query->leftJoinSub($stockSummary, 'stock_summary', function ($join) {
            $join->on('stock_summary.product_id', '=', 'products.id');
        });
    }
}
