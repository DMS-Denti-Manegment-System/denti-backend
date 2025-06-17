<?php
// ==============================================
// 1. Stock Model
// app/Modules/Stock/Models/Stock.php
// ==============================================

namespace App\Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'unit', 'category', 'brand',
        'supplier_id', 'purchase_price', 'purchase_date', 'expiry_date',
        'current_stock', 'reserved_stock', 'available_stock',
        'min_stock_level', 'critical_stock_level',
        'yellow_alert_level', 'red_alert_level',
        'internal_usage_count', 'status', 'track_expiry', 'track_batch',
        'clinic_id', 'storage_location'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
        'track_expiry' => 'boolean',
        'track_batch' => 'boolean',
    ];

    // İlişkiler
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(StockRequest::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    // Scope'lar
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= yellow_alert_level');
    }

    public function scopeCriticalStock($query)
    {
        return $query->whereRaw('current_stock <= red_alert_level');
    }

    public function scopeNearExpiry($query, $days = 30)
    {
        return $query->where('track_expiry', true)
                    ->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('track_expiry', true)
                    ->where('expiry_date', '<', now());
    }

    // Accessor'lar
    public function getStockStatusAttribute()
    {
        if ($this->current_stock <= $this->red_alert_level) return 'critical';
        if ($this->current_stock <= $this->yellow_alert_level) return 'low';
        return 'normal';
    }

    public function getIsExpiredAttribute()
    {
        return $this->track_expiry && $this->expiry_date < now();
    }

    public function getIsNearExpiryAttribute()
    {
        return $this->track_expiry &&
               $this->expiry_date <= now()->addDays(30) &&
               $this->expiry_date > now();
    }

    public function getDaysToExpiryAttribute()
    {
        if (!$this->track_expiry || !$this->expiry_date) return null;
        return now()->diffInDays($this->expiry_date, false);
    }
}