<?php

// ==============================================
// 5. StockTransaction Model
// app/Modules/Stock/Models/StockTransaction.php
// ==============================================

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransaction extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'transaction_number', 'stock_id', 'user_id', 'clinic_id', 'type',
        'quantity', 'previous_stock', 'new_stock',
        'unit_price', 'total_price', 'stock_request_id',
        'reference_number', 'batch_number', 'description',
        'notes', 'performed_by', 'transaction_date', 'company_id',
        'is_sub_unit',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'transaction_date' => 'datetime',
        'is_sub_unit' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function getTypeTextAttribute()
    {
        return match ($this->type) {
            'purchase' => 'Satın Alma',
            'usage' => 'Kullanım',
            'transfer_in' => 'Transfer Giriş',
            'transfer_out' => 'Transfer Çıkış',
            'adjustment' => 'Düzeltme',
            'adjustment_increase' => 'Stok Artışı (Düzeltme)',
            'adjustment_decrease' => 'Stok Azalışı (Düzeltme)',
            'expired' => 'Son Kullanma Tarihi Geçen',
            'damaged' => 'Hasarlı',
            'returned' => 'İade',
            default => 'Bilinmeyen'
        };
    }
}
