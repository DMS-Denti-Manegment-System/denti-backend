<?php
// ==============================================
// 3. Clinic Model
// app/Modules/Stock/Models/Clinic.php
// ==============================================

namespace App\Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'responsible_person',
        'phone', 'location', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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