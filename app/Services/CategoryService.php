<?php

// app/Services/CategoryService.php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\TodoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    protected $categoryRepository;

    protected $todoRepository;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        TodoRepositoryInterface $todoRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->todoRepository = $todoRepository;
    }

    public function getAllCategories(): Collection
    {
        return $this->categoryRepository->all();
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->find($id);
    }

    public function createCategory(array $data): Category
    {
        // Default değerler
        $data['is_active'] = $data['is_active'] ?? true;
        $data['color'] = $data['color'] ?? '#6B7280';

        return $this->categoryRepository->create($data);
    }

    public function updateCategory(int $id, array $data): ?Category
    {
        return $this->categoryRepository->update($id, $data);
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->categoryRepository->find($id);
        if (! $category) {
            return false;
        }

        if ($category->todos()->count() > 0) {
            throw new \Exception('Bu kategoriye ait todolar bulunmaktadır.');
        }

        return $this->categoryRepository->delete($id);
    }

    public function getCategoryStats(int $id): array
    {
        $category = $this->categoryRepository->find($id);
        if (! $category) {
            throw new \Exception('Kategori bulunamadı');
        }

        $todos = $category->todos;

        return [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'is_active' => $category->is_active,
            ],
            'stats' => [
                'total' => $todos->count(),
                'completed' => $todos->where('completed', true)->count(),
                'pending' => $todos->where('completed', false)->count(),
                'completion_rate' => $todos->count() > 0 ?
                    round(($todos->where('completed', true)->count() / $todos->count()) * 100, 2) : 0,
            ],
        ];
    }
}
