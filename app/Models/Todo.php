<?php
// app/Models/Todo.php - GÜNCELLENMİŞ HALİ

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $fillable = [
        'title',
        'description',
        'completed',
        'completed_at',
        'category_id'  // YENİ EKLENEN!
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime'
    ];

    /**
     * İLİŞKİLER
     */

    /**
     * Bu todo hangi kategoriye ait?
     * Many-to-One ilişkisi: Birçok todo -> Bir kategori
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * SCOPE'LAR - Mevcut scope'lar
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('completed', false);
    }

    /**
     * YENİ SCOPE: Kategoriye göre filtrele
     * Kullanım: Todo::byCategory(1)->get()
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * ACCESSOR'LAR
     */
    public function getIsOverdueAttribute()
    {
        return !$this->completed && $this->created_at->diffInDays(now()) > 7;
    }

    /**
     * YENİ ACCESSOR: Kategori adını getir
     * Kullanım: $todo->category_name
     */
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : 'Kategorisiz';
    }
}