<?php
// app/Repositories/TodoRepository.php - YENİ METHODLAR EKLENMİŞ

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

    // ... MEVCUT METHODLAR AYNI KALACAK ...

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
        return $this->model->completed()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPending(): Collection
    {
        return $this->model->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // YENİ METHODLAR

    /**
     * Belirli kategorideki todo'ları getir
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model
            ->byCategory($categoryId)  // Model scope kullanımı
            ->with('category')         // Kategori bilgisini de getir
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tüm todo'ları kategori bilgileriyle getir
     */
    public function getTodosWithCategories(): Collection
    {
        return $this->model
            ->with('category')  // Her todo için kategori bilgisi
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Kategorisi olmayan todo'ları getir
     */
    public function getUncategorizedTodos(): Collection
    {
        return $this->model
            ->whereNull('category_id')  // category_id = NULL
            ->orderBy('created_at', 'desc')
            ->get();
    }
}