<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'stock_id',
        'from_clinic_id',
        'to_clinic_id',
        'quantity',
        'notes',
        'status',
        'requested_by',
        'approved_by',
        'completed_by',
        'requested_at',
        'approved_at',
        'completed_at',
        'cancelled_at',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'quantity' => 'integer',
    ];

    // Durum sabitleri
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_IN_TRANSIT = 'in_transit';

    const STATUS_COMPLETED = 'completed';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CANCELLED = 'cancelled';

    // İlişkiler
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function fromClinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'from_clinic_id');
    }

    public function toClinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'to_clinic_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where(function ($q) use ($clinicId) {
            $q->where('from_clinic_id', $clinicId)
                ->orWhere('to_clinic_id', $clinicId);
        });
    }

    public function scopeOutgoing($query, int $clinicId)
    {
        return $query->where('from_clinic_id', $clinicId);
    }

    public function scopeIncoming($query, int $clinicId)
    {
        return $query->where('to_clinic_id', $clinicId);
    }

    // Helper metodlar
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canApprove(): bool
    {
        return $this->isPending();
    }

    public function canReject(): bool
    {
        return $this->isPending();
    }

    public function canComplete(): bool
    {
        return $this->isApproved() || $this->status === self::STATUS_IN_TRANSIT;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    // Status label
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_APPROVED => 'Onaylandı',
            self::STATUS_IN_TRANSIT => 'Transfer Sürecinde',
            self::STATUS_COMPLETED => 'Tamamlandı',
            self::STATUS_REJECTED => 'Reddedildi',
            self::STATUS_CANCELLED => 'İptal Edildi',
            default => $this->status,
        };
    }

    // Status color (UI için)
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'orange',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_IN_TRANSIT => 'cyan',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'default',
        };
    }
}
