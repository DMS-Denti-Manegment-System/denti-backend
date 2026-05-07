<?php

namespace App\Services;

use App\Models\Todo;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\TodoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TodoService
{
    protected $todoRepository;

    protected $categoryRepository;

    public function __construct(
        TodoRepositoryInterface $todoRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->todoRepository = $todoRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function getAllTodos(): Collection
    {
        return $this->todoRepository->getWithCategory();
    }

    public function getTodoById(int $id): ?Todo
    {
        return $this->todoRepository->find($id);
    }

    public function createTodo(array $data): Todo
    {
        // Default değerleri set et
        $data['completed'] = false;

        // Category kontrolü
        if (isset($data['category_id'])) {
            $category = $this->categoryRepository->find($data['category_id']);
            if (! $category || ! $category->is_active) {
                throw new \Exception('Geçersiz veya aktif olmayan kategori');
            }
        }

        return $this->todoRepository->create($data);
    }

    public function updateTodo(int $id, array $data): ?Todo
    {
        // Tamamlanma tarihi
        if (isset($data['completed']) && $data['completed']) {
            $data['completed_at'] = now();
        } elseif (isset($data['completed']) && ! $data['completed']) {
            $data['completed_at'] = null;
        }

        // Category kontrolü
        if (isset($data['category_id'])) {
            $category = $this->categoryRepository->find($data['category_id']);
            if (! $category || ! $category->is_active) {
                throw new \Exception('Geçersiz veya aktif olmayan kategori');
            }
        }

        return $this->todoRepository->update($id, $data);
    }

    public function deleteTodo(int $id): bool
    {
        $todo = $this->todoRepository->find($id);
        if (! $todo) {
            return false;
        }

        if ($todo->completed) {
            throw new \Exception('Tamamlanmış todolar silinemez');
        }

        return $this->todoRepository->delete($id);
    }

    public function toggleTodoStatus(int $id): ?Todo
    {
        $todo = $this->todoRepository->find($id);
        if ($todo) {
            $newStatus = ! $todo->completed;

            return $this->updateTodo($id, ['completed' => $newStatus]);
        }

        return null;
    }

    public function getTodosByCategory(int $categoryId): Collection
    {
        return $this->todoRepository->getByCategory($categoryId);
    }

    public function getTodoStats(): array
    {
        $all = $this->todoRepository->all();
        $completed = $this->todoRepository->getCompleted();
        $pending = $this->todoRepository->getPending();

        $categories = $this->categoryRepository->getWithTodos();
        $categoryStats = [];

        foreach ($categories as $category) {
            $categoryStats[] = [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'total' => $category->todos->count(),
                'completed' => $category->todos->where('completed', true)->count(),
            ];
        }

        return [
            'total' => $all->count(),
            'completed' => $completed->count(),
            'pending' => $pending->count(),
            'completion_rate' => $all->count() > 0 ?
                round(($completed->count() / $all->count()) * 100, 2) : 0,
            'categories' => $categoryStats,
        ];
    }
}
