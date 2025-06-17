<?php
// ==============================================
// 2. Supplier Model
// app/Modules/Stock/Models/Supplier.php
// ==============================================

namespace App\Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'contact_person', 'phone', 'email', 'address',
        'tax_number', 'is_active', 'additional_info'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'additional_info' => 'array',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getActiveStocksCountAttribute()
    {
        return $this->stocks()->active()->count();
    }

    public function getTotalStockValueAttribute()
    {
        return $this->stocks()
                   ->active()
                   ->sum(\DB::raw('current_stock * purchase_price'));
    }
}