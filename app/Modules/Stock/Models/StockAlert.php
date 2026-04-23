<?php
// ==============================================
// 6. StockAlert Model
// app/Modules/Stock/Models/StockAlert.php
// ==============================================

namespace App\Modules\Stock\Models;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends Model
{
    use Tenantable;

    protected $fillable = [
        'stock_id', 'clinic_id', 'type', 'title', 'message',
        'current_stock_level', 'threshold_level', 'expiry_date',
        'is_active', 'is_resolved', 'resolved_at', 'resolved_by',
        'company_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected $appends = ['severity'];

    /**
     * Get the severity based on alert type.
     */
    public function getSeverityAttribute(): string
    {
        return match($this->type) {
            'critical_stock', 'expired' => 'critical',
            'low_stock', 'near_expiry' => 'high',
            default => 'medium'
        };
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_resolved', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'low_stock' => 'Düşük Stok',
            'critical_stock' => 'Kritik Stok',
            'expired' => 'Süresi Geçen',
            'near_expiry' => 'Süresi Yaklaşan',
            default => 'Bilinmeyen'
        };
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'low_stock' => 'orange',
            'critical_stock' => 'red',
            'expired' => 'red',
            'near_expiry' => 'orange',
            default => 'gray'
        };
    }
}
