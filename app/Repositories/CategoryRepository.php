<?php
// app/Repositories/CategoryRepository.php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    protected $model;

    /**
     * Dependency Injection ile Category model'ini alıyoruz
     */
    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    /**
     * Tüm kategorileri getir (en yeniler önce)
     */
    public function all(): Collection
    {
        return $this->model
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * ID'ye göre kategori bul
     */
    public function find(int $id): ?Category
    {
        return $this->model->find($id);
    }

    /**
     * Yeni kategori oluştur
     */
    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    /**
     * Kategori güncelle
     */
    public function update(int $id, array $data): ?Category
    {
        $category = $this->find($id);
        if ($category) {
            $category->update($data);
            return $category;
        }
        return null;
    }

    /**
     * Kategori sil
     */
    public function delete(int $id): bool
    {
        $category = $this->find($id);
        return $category ? $category->delete() : false;
    }

    /**
     * Sadece aktif kategorileri getir
     * Model'deki scope'u kullanıyoruz!
     */
    public function getActiveCategories(): Collection
    {
        return $this->model
            ->active()  // scope kullanımı
            ->orderBy('name')
            ->get();
    }

    /**
     * Kategoriyi todo'larıyla birlikte getir
     * Lazy Loading yerine Eager Loading kullanıyoruz
     */
    public function getCategoryWithTodos(int $id): ?Category
    {
        return $this->model
            ->with('todos')  // İlişkili todo'ları da getir
            ->find($id);
    }

    /**
     * Kategorileri todo sayısıyla birlikte getir
     * withCount() Laravel'in harika özelliği!
     */
    public function getCategoriesWithTodoCount(): Collection
    {
        return $this->model
            ->withCount('todos')  // todos_count alanı otomatik eklenir
            ->orderBy('name')
            ->get();
    }

    /**
     * İsme göre arama yap
     */
    public function searchByName(string $name): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$name}%")
            ->active()
            ->get();
    }
}