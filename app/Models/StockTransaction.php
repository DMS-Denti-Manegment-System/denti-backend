<?php

// ==============================================
// 5. StockTransaction Model
// app/Modules/Stock/Models/StockTransaction.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $transaction_number
 * @property int $stock_id
 * @property int $user_id
 * @property int $clinic_id
 * @property string $type
 * @property int $quantity
 * @property int $previous_stock
 * @property int $new_stock
 * @property float|null $unit_price
 * @property float|null $total_price
 * @property int|null $stock_request_id
 * @property string|null $reference_number
 * @property string|null $batch_number
 * @property string|null $description
 * @property string|null $notes
 * @property string|null $performed_by
 * @property \Illuminate\Support\Carbon|null $transaction_date
 * @property bool $is_sub_unit
 * @property \Illuminate\Support\Carbon|null $reversed_at
 * @property int|null $reversed_by
 * @property int|null $reversal_transaction_id
 * @property int|null $company_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $type_text
 * @property-read \App\Models\Stock $stock
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Clinic $clinic
 * @property-read \App\Models\StockRequest|null $stockRequest
 *
 * @method static \Illuminate\Database\Eloquent\Builder|StockTransaction byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder|StockTransaction byDateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder|StockTransaction query()
 */
class StockTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_number', 'stock_id', 'user_id', 'clinic_id', 'type',
        'quantity', 'previous_stock', 'new_stock',
        'unit_price', 'total_price', 'stock_request_id',
        'reference_number', 'batch_number', 'description',
        'notes', 'performed_by', 'transaction_date',
        'is_sub_unit', 'reversed_at', 'reversed_by', 'reversal_transaction_id',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'transaction_date' => 'datetime',
        'is_sub_unit' => 'boolean',
        'reversed_at' => 'datetime',
    ];

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

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_transaction_id');
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
