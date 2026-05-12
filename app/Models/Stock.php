<?php

// app/Modules/Stock/Models/Stock.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $product_id
 * @property string|null $batch_code
 * @property int|null $supplier_id
 * @property float|null $purchase_price
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property int $current_stock
 * @property int $reserved_stock
 * @property int $available_stock
 * @property int $internal_usage_count
 * @property \App\Enums\StockStatus $status
 * @property bool $is_active
 * @property bool $track_expiry
 * @property bool $track_batch
 * @property int|null $expiry_yellow_days
 * @property int|null $expiry_red_days
 * @property int $clinic_id
 * @property string|null $storage_location
 * @property bool $has_sub_unit
 * @property string|null $sub_unit_name
 * @property int|null $sub_unit_multiplier
 * @property int $current_sub_stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int $total_base_units
 * @property-read string $stock_status
 * @property-read bool $is_expired
 * @property-read bool $is_near_expiry
 * @property-read string $expiry_status
 * @property-read int|null $days_to_expiry
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Supplier|null $supplier
 * @property-read \App\Models\Clinic $clinic
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StockTransaction[] $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StockRequest[] $requests
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StockAlert[] $alerts
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Stock active()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock inactive()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock lowStock()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock criticalStock()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock nearExpiry(?int $days = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Stock expired()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock query()
 * @method static \Illuminate\Database\Eloquent\Builder|Stock where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
class Stock extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [];

    protected $fillable = [
        'product_id', 'batch_code', 'supplier_id', 'purchase_price', 'currency', 'purchase_date', 'expiry_date',
        'current_stock', 'reserved_stock', 'available_stock', 'internal_usage_count',
        'status', 'is_active', 'track_expiry', 'track_batch',
        'expiry_yellow_days', 'expiry_red_days',
        'clinic_id', 'storage_location',
        'has_sub_unit', 'sub_unit_name', 'sub_unit_multiplier', 'current_sub_stock',
    ];

    // İlişkiler
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
        'track_expiry' => 'boolean',
        'track_batch' => 'boolean',
        'is_active' => 'boolean',
        'has_sub_unit' => 'boolean',
        'sub_unit_multiplier' => 'integer',
        'current_sub_stock' => 'integer',
        'expiry_yellow_days' => 'integer',
        'expiry_red_days' => 'integer',
        'status' => \App\Enums\StockStatus::class,
    ];

    protected $attributes = [
        'is_active' => true,
        'status' => 'active',
        'track_expiry' => true,
        'track_batch' => false,
        'currency' => 'TRY',
        'current_stock' => 0,
        'reserved_stock' => 0,
        'available_stock' => 0,
        'has_sub_unit' => false,
        'current_sub_stock' => 0,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Clinic::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Models\StockTransaction::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(\App\Models\StockRequest::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(\App\Models\StockAlert::class);
    }

    /**
     * Toplam baz birim miktarını hesaplayan Raw SQL ifadesini döner.
     * DRY ilkesi için merkezi olarak tanımlanmıştır.
     */
    public static function totalBaseUnitsRaw(): string
    {
        return '(CASE 
                    WHEN stocks.has_sub_unit THEN (stocks.current_stock * COALESCE(stocks.sub_unit_multiplier, 1)) + stocks.current_sub_stock 
                    ELSE stocks.current_stock 
                 END)';
    }

    // Scope'lar
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeLowStock($query)
    {
        return $query->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->whereRaw(self::totalBaseUnitsRaw().' <= COALESCE(products.yellow_alert_level, products.min_stock_level)')
            ->whereRaw('('.self::totalBaseUnitsRaw().' > COALESCE(products.red_alert_level, products.critical_stock_level) OR ('.self::totalBaseUnitsRaw().' = 0 AND NOT COALESCE(products.show_zero_stock_in_critical, true)))')
            ->where('stocks.is_active', true)
            ->select('stocks.*');
    }

    public function scopeCriticalStock($query)
    {
        return $query->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->whereRaw(self::totalBaseUnitsRaw().' <= COALESCE(products.red_alert_level, products.critical_stock_level)')
            ->whereRaw('NOT ('.self::totalBaseUnitsRaw().' = 0 AND NOT COALESCE(products.show_zero_stock_in_critical, true))')
            ->where('stocks.is_active', true)
            ->select('stocks.*');
    }

    public function scopeNearExpiry($query, $days = null)
    {
        return $query->where('track_expiry', true)
            ->where('expiry_date', '<=', today()->addDays($days ?? 30))
            ->where('expiry_date', '>', today())
            ->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('track_expiry', true)
            ->where('expiry_date', '<', today())
            ->where('is_active', true);
    }

    // Accessor'lar
    public function getTotalBaseUnitsAttribute()
    {
        if (! $this->has_sub_unit) {
            return $this->current_stock;
        }

        return ($this->current_stock * ($this->sub_unit_multiplier ?? 1)) + $this->current_sub_stock;
    }

    public function getStockStatusAttribute()
    {
        if (! $this->is_active) {
            return \App\Enums\StockStatus::INACTIVE->value;
        }

        $product = $this->product;
        if (! $product) {
            return 'normal';
        }

        $total = $this->total_base_units;
        $redLevel = $product->red_alert_level ?? $product->critical_stock_level;
        $yellowLevel = $product->yellow_alert_level ?? $product->min_stock_level;
        $hideZeroFromCritical = ! ($product->show_zero_stock_in_critical ?? true);

        if ($total <= $redLevel && ! ($total === 0 && $hideZeroFromCritical)) {
            return 'critical';
        }
        if ($total <= $yellowLevel) {
            return \App\Enums\StockStatus::LOW_STOCK->value;
        }

        return 'normal';
    }

    public function getIsExpiredAttribute()
    {
        return $this->track_expiry && $this->expiry_date < today();
    }

    public function getIsNearExpiryAttribute()
    {
        return $this->track_expiry &&
               $this->expiry_date <= today()->addDays($this->expiry_yellow_days ?? 30) &&
               $this->expiry_date > today();
    }

    public function getExpiryStatusAttribute()
    {
        if (! $this->track_expiry || ! $this->expiry_date) {
            return 'normal';
        }
        if ($this->expiry_date < today()) {
            return 'expired';
        }

        $days = $this->days_to_expiry;
        if ($days <= ($this->expiry_red_days ?? 15)) {
            return 'critical';
        }
        if ($days <= ($this->expiry_yellow_days ?? 30)) {
            return 'warning';
        }

        return 'normal';
    }

    public function getDaysToExpiryAttribute()
    {
        if (! $this->track_expiry || ! $this->expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date, false);
    }

    public function getAvailableStockAttribute($value)
    {
        return $this->current_stock - $this->reserved_stock;
    }

    // Model Events
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($stock) {
            // Soft delete sırasında status'ü güncelle ve uyarılrı sil
            $stock->status = \App\Enums\StockStatus::DELETED;
            $stock->saveQuietly();

            // Bağlı uyarıları sil
            $stock->alerts()->delete();
        });
    }
}
