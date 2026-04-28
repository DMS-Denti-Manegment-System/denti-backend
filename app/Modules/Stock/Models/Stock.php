<?php
// app/Modules/Stock/Models/Stock.php

namespace App\Modules\Stock\Models;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use Tenantable, SoftDeletes;
    
    protected $appends = [];

    protected $fillable = [
        'name', 'code', 'description', 'unit', 'category', 'brand',
        'supplier_id', 'purchase_price', 'currency', 'purchase_date', 'expiry_date',
        'current_stock', 'reserved_stock', 'available_stock',
        'min_stock_level', 'critical_stock_level',
        'yellow_alert_level', 'red_alert_level',
        'internal_usage_count', 'status', 'is_active', 'track_expiry', 'track_batch',
        'expiry_yellow_days', 'expiry_red_days',
        'clinic_id', 'storage_location',
        'has_sub_unit', 'sub_unit_name', 'sub_unit_multiplier', 'current_sub_stock',
        'company_id'
    ];

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
        'internal_usage_count' => 0,
        'has_sub_unit' => false,
        'current_sub_stock' => 0,
    ];

    // İlişkiler
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Stock\Models\Supplier::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Stock\Models\Clinic::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Modules\Stock\Models\StockTransaction::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(\App\Modules\Stock\Models\StockRequest::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(\App\Modules\Stock\Models\StockAlert::class);
    }

    /**
     * Toplam baz birim miktarını hesaplayan Raw SQL ifadesini döner.
     * DRY ilkesi için merkezi olarak tanımlanmıştır.
     */
    public static function totalBaseUnitsRaw(): string
    {
        return "(CASE 
                    WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock 
                    ELSE current_stock 
                 END)";
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
        return $query->whereRaw(self::totalBaseUnitsRaw() . ' <= COALESCE(yellow_alert_level, min_stock_level)')
                     ->where('is_active', true);
    }

    public function scopeCriticalStock($query)
    {
        return $query->whereRaw(self::totalBaseUnitsRaw() . ' <= COALESCE(red_alert_level, critical_stock_level)')
                     ->where('is_active', true);
    }

    public function scopeNearExpiry($query, $days = null)
    {
        return $query->where('track_expiry', true)
                    ->where(function ($q) use ($days) {
                        $q->where('expiry_date', '<=', now()->addDays($days ?? 30)) // Fallback if needed
                          ->orWhereRaw('expiry_date <= DATE_ADD(NOW(), INTERVAL expiry_yellow_days DAY)');
                    })
                    ->where('expiry_date', '>', now())
                    ->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('track_expiry', true)
                    ->where('expiry_date', '<', now())
                    ->where('is_active', true);
    }

    // Accessor'lar
    public function getTotalBaseUnitsAttribute()
    {
        if (!$this->has_sub_unit) {
            return $this->current_stock;
        }
        return ($this->current_stock * ($this->sub_unit_multiplier ?? 1)) + $this->current_sub_stock;
    }

    public function getStockStatusAttribute()
    {
        if (!$this->is_active) return 'inactive';
        
        $total = $this->total_base_units;
        $redLevel = $this->red_alert_level ?? $this->critical_stock_level;
        $yellowLevel = $this->yellow_alert_level ?? $this->min_stock_level;

        if ($total <= $redLevel) return 'critical';
        if ($total <= $yellowLevel) return 'low';
        return 'normal';
    }

    public function getIsExpiredAttribute()
    {
        return $this->track_expiry && $this->expiry_date < now();
    }

    public function getIsNearExpiryAttribute()
    {
        return $this->track_expiry &&
               $this->expiry_date <= now()->addDays($this->expiry_yellow_days ?? 30) &&
               $this->expiry_date > now();
    }

    public function getExpiryStatusAttribute()
    {
        if (!$this->track_expiry || !$this->expiry_date) return 'normal';
        if ($this->expiry_date < now()) return 'expired';
        
        $days = $this->days_to_expiry;
        if ($days <= ($this->expiry_red_days ?? 15)) return 'critical';
        if ($days <= ($this->expiry_yellow_days ?? 30)) return 'warning';
        
        return 'normal';
    }

    public function getDaysToExpiryAttribute()
    {
        if (!$this->track_expiry || !$this->expiry_date) return null;
        return now()->diffInDays($this->expiry_date, false);
    }

    // Model Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($stock) {
            if (!isset($stock->is_active)) {
                $stock->is_active = true;
            }
        });

        static::deleting(function ($stock) {
            // Soft delete sırasında status'ü güncelle ve uyarılrı sil
            $stock->status = 'deleted';
            $stock->saveQuietly();
            
            // Bağlı uyarıları sil
            $stock->alerts()->delete();
        });
    }
}
