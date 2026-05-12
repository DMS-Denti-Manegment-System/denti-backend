<?php

// app/Repositories/TodoRepository.php

namespace App\Repositories;

use App\Models\Todo;
use App\Repositories\Interfaces\TodoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TodoRepository implements TodoRepositoryInterface
{
    protected $model;

    public function __construct(Todo $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->orderBy('created_at', 'desc')->get();
    }

    public function find(int $id): ?Todo
    {
        return $this->model->find($id);
    }

    public function create(array $data): Todo
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Todo
    {
        $todo = $this->find($id);
        if ($todo) {
            $todo->update($data);

            return $todo;
        }

        return null;
    }

    public function delete(int $id): bool
    {
        $todo = $this->find($id);

        return $todo ? $todo->delete() : false;
    }

    public function getCompleted(): Collection
    {
        return $this->model->where('completed', true)->orderBy('completed_at', 'desc')->get();
    }

    public function getPending(): Collection
    {
        return $this->model->where('completed', false)->orderBy('created_at', 'desc')->get();
    }

    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->where('category_id', $categoryId)->orderBy('created_at', 'desc')->get();
    }

    public function getWithCategory(): Collection
    {
        return $this->model->with('category')->orderBy('created_at', 'desc')->get();
    }
 
    public function getAllWithFilters(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with('category');
 
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where('title', 'like', $search);
        }
 
        if (! empty($filters['status'])) {
            $query->where('completed', $filters['status'] === 'completed');
        }
 
        return $query->latest()->paginate($perPage);
    }
}
