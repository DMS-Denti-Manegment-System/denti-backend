<?php

// app/Repositories/CategoryRepository.php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    protected $model;

    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->orderBy('name', 'asc')->get();
    }

    public function find(int $id): ?Category
    {
        return $this->model->find($id);
    }

    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Category
    {
        $category = $this->find($id);
        if ($category) {
            $category->update($data);

            return $category;
        }

        return null;
    }

    public function delete(int $id): bool
    {
        $category = $this->find($id);

        return $category ? $category->delete() : false;
    }

    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->orderBy('name', 'asc')->get();
    }

    public function getWithTodos(): Collection
    {
        return $this->model->with('todos')->orderBy('name', 'asc')->get();
    }
}
