<?php

// ==============================================
// 4. StockRequest Model
// app/Modules/Stock/Models/StockRequest.php
// ==============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
