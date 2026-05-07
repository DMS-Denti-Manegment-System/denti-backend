<?php

// ==============================================
// 3. Clinic Model
// app/Modules/Stock/Models/Clinic.php
// ==============================================

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'name', 'description', 'responsible_person',
        'phone', 'location', 'is_active', 'company_id',
        'email', 'address', 'city', 'district',
        'postal_code', 'website', 'opening_hours',
    ];

    /**
     * Get the company that owns the clinic.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function requestedStocks(): HasMany
    {
        return $this->hasMany(StockRequest::class, 'requester_clinic_id');
    }

    public function receivedRequests(): HasMany
    {
        return $this->hasMany(StockRequest::class, 'requested_from_clinic_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalStockItemsAttribute()
    {
        return $this->stocks()->active()->count();
    }

    public function getTotalStockQuantityAttribute()
    {
        return $this->stocks()->active()->sum('current_stock');
    }

    public function getLowStockItemsCountAttribute()
    {
        return $this->stocks()->active()->lowStock()->count();
    }

    public function getCriticalStockItemsCountAttribute()
    {
        return $this->stocks()->active()->criticalStock()->count();
    }
}
