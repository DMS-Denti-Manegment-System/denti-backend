<?php

// ==============================================
// 4. StockRequest Model
// app/Modules/Stock/Models/StockRequest.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $request_number
 * @property int $requester_clinic_id
 * @property int $requested_from_clinic_id
 * @property int $stock_id
 * @property int $requested_quantity
 * @property int|null $approved_quantity
 * @property string $status
 * @property string|null $request_reason
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $requested_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int $requested_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read bool $can_be_approved
 * @property-read string $status_color
 *
 * @property-read \App\Models\Clinic $requesterClinic
 * @property-read \App\Models\Clinic $requestedFromClinic
 * @property-read \App\Models\Stock $stock
 *
 * @method static \Illuminate\Database\Eloquent\Builder|StockRequest pending()
 * @method static \Illuminate\Database\Eloquent\Builder|StockRequest approved()
 * @method static \Illuminate\Database\Eloquent\Builder|StockRequest inTransit()
 * @method static \Illuminate\Database\Eloquent\Builder|StockRequest completed()
 * @method static \Illuminate\Database\Eloquent\Builder|StockRequest query()
 */
class StockRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_number', 'requester_clinic_id', 'requested_from_clinic_id',
        'stock_id', 'requested_quantity', 'approved_quantity', 'status',
        'request_reason', 'admin_notes', 'rejection_reason',
        'requested_at', 'approved_at', 'completed_at',
        'requested_by', 'approved_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function requesterClinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'requester_clinic_id');
    }

    public function requestedFromClinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'requested_from_clinic_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getCanBeApprovedAttribute()
    {
        return $this->status === 'pending' &&
               $this->stock->available_stock >= $this->requested_quantity;
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pending' => 'orange',
            'approved' => 'blue',
            'in_transit' => 'cyan',
            'completed' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }
}
