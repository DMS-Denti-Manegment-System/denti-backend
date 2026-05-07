<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'name', 'sku', 'description', 'unit', 'category', 'brand',
        'min_stock_level', 'critical_stock_level',
        'yellow_alert_level', 'red_alert_level',
        'is_active', 'has_expiration_date', 'company_id', 'clinic_id',
        'has_sub_unit', 'sub_unit_name', 'sub_unit_multiplier',
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
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Stock::class, 'product_id');
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

        if ($total <= $redLevel) {
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
        $lastBatch = $this->getLoadedOrFreshBatches()
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
}
