<?php

// ==============================================
// 3. Clinic Model
// app/Modules/Stock/Models/Clinic.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $responsible_person
 * @property string|null $phone
 * @property string|null $location
 * @property bool $is_active
 * @property string|null $email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $district
 * @property string|null $postal_code
 * @property string|null $website
 * @property string|null $opening_hours
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read int $total_stock_items
 * @property-read int $total_stock_quantity
 * @property-read int $low_stock_items_count
 * @property-read int $critical_stock_items_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Clinic active()
 */
class Clinic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'responsible_person',
        'phone', 'location', 'is_active',
        'email', 'address', 'city', 'district',
        'postal_code', 'website', 'opening_hours',
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
