<?php
// app/Services/CategoryService.php - MİNİMAL VERSİYON

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Şimdilik Repository kullanmadan, direkt Model ile çalışalım
     */
    public function getAllCategories(): Collection
    {
        return Category::orderBy('created_at', 'desc')->get();
    }

    public function getCategoriesWithStats(): Collection
    {
        return Category::withCount('todos')->orderBy('name')->get();
    }

    public function getCategoryById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function createCategory(array $data): Category
    {
        // Basit business logic
        if (strlen($data['name']) < 2) {
            throw new \Exception('Kategori adı en az 2 karakter olmalı!');
        }

        // Default değerler
        $data['color'] = $data['color'] ?? '#6B7280';
        $data['is_active'] = $data['is_active'] ?? true;

        return Category::create($data);
    }

    public function updateCategory(int $id, array $data): ?Category
    {
        $category = Category::find($id);
        if (!$category) {
            throw new \Exception('Kategori bulunamadı!');
        }

        $category->update($data);
        return $category;
    }

    public function deleteCategory(int $id): bool
    {
        $category = Category::find($id);
        if (!$category) {
            throw new \Exception('Kategori bulunamadı!');
        }

        return $category->delete();
    }

    public function searchCategories(string $query): Collection
    {
        return Category::where('name', 'LIKE', "%{$query}%")->get();
    }

    public function toggleCategoryStatus(int $id): ?Category
    {
        $category = Category::find($id);
        if (!$category) {
            throw new \Exception('Kategori bulunamadı!');
        }

        $category->update(['is_active' => !$category->is_active]);
        return $category;
    }
}