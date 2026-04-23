<?php

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

    /**
     * Tüm todo'ları oluşturulma tarihine göre azalan sırada (en yeni önce) getirir.
     */
    public function all(): Collection
    {
        return $this->model->orderBy('created_at', 'desc')->get();
    }

    /**
     * ID'ye göre tek bir todo kaydı getirir. Bulunamazsa null döner.
     */
    public function find(int $id): ?Todo
    {
        return $this->model->find($id);
    }

    /**
     * Yeni bir todo kaydı oluşturur.
     */
    public function create(array $data): Todo
    {
        return $this->model->create($data);
    }

    /**
     * Mevcut bir todo kaydını günceller. Bulunamazsa null döner.
     */
    public function update(int $id, array $data): ?Todo
    {
        $todo = $this->find($id);
        if ($todo) {
            $todo->update($data);
            return $todo;
        }
        return null;
    }

    /**
     * Belirtilen ID'li todo kaydını siler. Başarı durumunda true döner.
     */
    public function delete(int $id): bool
    {
        $todo = $this->find($id);
        return $todo ? $todo->delete() : false;
    }

    /**
     * Tamamlanmış (completed) todo'ları getirir.
     * Todo modeli üzerinde 'scopeCompleted' scope'u tanımlı olmalıdır.
     */
    public function getCompleted(): Collection
    {
        return $this->model->completed()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Bekleyen (pending/incomplete) todo'ları getirir.
     * Todo modeli üzerinde 'scopePending' scope'u tanımlı olmalıdır.
     */
    public function getPending(): Collection
    {
        return $this->model->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Belirli bir kategoriye ait todo'ları kategori bilgisiyle birlikte getirir.
     *
     * Bağımlılık: Todo modelinde 'scopeByCategory(Builder $query, int $categoryId)'
     * scope'u tanımlı olmalıdır.
     *
     * @param  int  $categoryId  Filtrelenecek kategori ID'si
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model
            ->byCategory($categoryId)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tüm todo'ları ilgili kategori bilgisiyle birlikte (eager load) getirir.
     */
    public function getTodosWithCategories(): Collection
    {
        return $this->model
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Kategorisi atanmamış (category_id IS NULL) todo'ları getirir.
     */
    public function getUncategorizedTodos(): Collection
    {
        return $this->model
            ->whereNull('category_id')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}


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