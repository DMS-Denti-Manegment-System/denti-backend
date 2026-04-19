<?php
// app/Models/Category.php

namespace App\Models;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use Tenantable;

    /**
     * Toplu atama için izin verilen alanlar
     * Bu alanlar $category->create([...]) ile doldurulabilir
     */
    protected $fillable = [
        'name',
        'color',
        'description',
        'is_active',
        'company_id'
    ];

    /**
     * Otomatik tip dönüşümleri
     * Veritabanından gelen veri otomatik olarak bu tiplere dönüştürülür
     */
    protected $casts = [
        'is_active' => 'boolean',  // 0/1 -> true/false
    ];

    /**
     * İLİŞKİLER - Burada Laravel'in gücünü görüyoruz!
     */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Bu kategoriye ait tüm todo'ları getir
     * One-to-Many ilişkisi: Bir kategori -> Birçok todo
     */
    public function todos()
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * SCOPE'LAR - Tekrar kullanılabilir sorgu parçaları
     */

    /**
     * Sadece aktif kategorileri getir
     * Kullanım: Category::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ACCESSOR'LAR - Computed properties
     */

    /**
     * Bu kategorideki todo sayısını getir
     * Kullanım: $category->todos_count
     */
    public function getTodosCountAttribute()
    {
        return $this->todos()->count();
    }
}
