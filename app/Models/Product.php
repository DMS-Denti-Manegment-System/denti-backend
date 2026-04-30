<?php

namespace App\Models;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Tenantable, SoftDeletes;

    protected $fillable = [
        'name', 'sku', 'description', 'unit', 'category', 'brand',
        'min_stock_level', 'critical_stock_level',
        'yellow_alert_level', 'red_alert_level',
        'is_active', 'has_expiration_date', 'company_id', 'clinic_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_expiration_date' => 'boolean',
        'min_stock_level' => 'integer',
        'critical_stock_level' => 'integer',
        'yellow_alert_level' => 'integer',
        'red_alert_level' => 'integer',
        'clinic_id' => 'integer',
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
        return $this->batches->sum(function($batch) {
            if (!$batch->has_sub_unit) {
                return $batch->current_stock;
            }
            return ($batch->current_stock * ($batch->sub_unit_multiplier ?? 1)) + $batch->current_sub_stock;
        });
    }

    public function getStockStatusAttribute()
    {
        if (!$this->is_active) return \App\Enums\StockStatus::INACTIVE->value;
        
        $total = $this->total_stock;
        $redLevel = $this->red_alert_level ?? $this->critical_stock_level;
        $yellowLevel = $this->yellow_alert_level ?? $this->min_stock_level;

        if ($total <= $redLevel) return 'critical';
        if ($total <= $yellowLevel) return \App\Enums\StockStatus::LOW_STOCK->value;
        return 'normal';
    }
}
