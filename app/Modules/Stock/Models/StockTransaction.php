<?php

// ==============================================
// 5. StockTransaction Model
// app/Modules/Stock/Models/StockTransaction.php
// ==============================================

namespace App\Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransaction extends Model
{
    protected $fillable = [
        'transaction_number', 'stock_id', 'clinic_id', 'type',
        'quantity', 'previous_stock', 'new_stock',
        'unit_price', 'total_price', 'stock_request_id',
        'reference_number', 'batch_number', 'description',
        'notes', 'performed_by', 'transaction_date'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
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
        return match($this->type) {
            'purchase' => 'Satın Alma',
            'usage' => 'Kullanım',
            'transfer_in' => 'Transfer Giriş',
            'transfer_out' => 'Transfer Çıkış',
            'adjustment' => 'Düzeltme',
            'expired' => 'Son Kullanma Tarihi Geçen',
            'damaged' => 'Hasarlı',
            'returned' => 'İade',
            default => 'Bilinmeyen'
        };
    }
}