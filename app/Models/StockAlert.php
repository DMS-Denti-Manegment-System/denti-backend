<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $product_id
 * @property int $stock_id
 * @property int $clinic_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property int|null $current_stock_level
 * @property int|null $threshold_level
 * @property string|null $expiry_date
 * @property bool $is_active
 * @property bool $is_resolved
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property string|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Stock $stock
 * @property-read \App\Models\Clinic $clinic
 *
 * @method static Builder|StockAlert active()
 * @method static Builder|StockAlert byType(string $type)
 */
class StockAlert extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'stock_id',
        'clinic_id',
        'type',
        'title',
        'message',
        'current_stock_level',
        'threshold_level',
        'expiry_date',
        'is_active',
        'is_resolved',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'threshold_level' => 'integer',
        'current_stock_level' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_resolved', false);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function getSeverityAttribute(): string
    {
        return in_array($this->type, ['critical_stock', 'expired']) ? 'critical' : 'warning';
    }
}
