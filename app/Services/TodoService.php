<?php
// app/Services/TodoService.php - BASİTLEŞTİRİLMİŞ

namespace App\Services;

use App\Models\Todo;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class TodoService
{
    /**
     * Şimdilik Repository kullanmadan, direkt Model ile çalışalım
     */
    public function getAllTodos(): Collection
    {
        return Todo::with('category')->orderBy('created_at', 'desc')->get();
    }

    public function getTodoById(int $id): ?Todo
    {
        return Todo::with('category')->find($id);
    }

    public function createTodo(array $data): Todo
    {
        // Basit business logic
        if (strlen($data['title']) < 3) {
            throw new \Exception('Todo başlığı en az 3 karakter olmalıdır.');
        }

        // Kategori kontrolü
        if (isset($data['category_id']) && $data['category_id'] !== null) {
            $category = Category::find($data['category_id']);
            if (!$category) {
                throw new \Exception('Seçilen kategori bulunamadı!');
            }
            if (!$category->is_active) {
                throw new \Exception('Pasif kategoriye todo eklenemez!');
            }
        }

        // Default değerler
        $data['completed'] = false;

        return Todo::create($data);
    }

    public function updateTodo(int $id, array $data): ?Todo
    {
        $todo = Todo::find($id);
        if (!$todo) {
            throw new \Exception('Todo bulunamadı!');
        }

        // Kategori kontrolü
        if (isset($data['category_id']) && $data['category_id'] !== null) {
            $category = Category::find($data['category_id']);
            if (!$category || !$category->is_active) {
                throw new \Exception('Geçersiz kategori!');
            }
        }

        // Tamamlanma tarihi business logic'i
        if (isset($data['completed']) && $data['completed']) {
            $data['completed_at'] = now();
        } elseif (isset($data['completed']) && !$data['completed']) {
            $data['completed_at'] = null;
        }

        $todo->update($data);
        return $todo;
    }

    public function deleteTodo(int $id): bool
    {
        $todo = Todo::find($id);
        if (!$todo) {
            return false;
        }

        return $todo->delete();
    }

    public function toggleTodoStatus(int $id): ?Todo
    {
        $todo = Todo::find($id);
        if ($todo) {
            $newStatus = !$todo->completed;
            return $this->updateTodo($id, ['completed' => $newStatus]);
        }
        return null;
    }

    /**
     * Kategoriye göre todo'ları getir
     */
    public function getTodosByCategory(int $categoryId): Collection
    {
        $category = Category::find($categoryId);
        if (!$category) {
            throw new \Exception('Kategori bulunamadı!');
        }

        return Todo::where('category_id', $categoryId)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Kategorisi olmayan todo'ları getir
     */
    public function getUncategorizedTodos(): Collection
    {
        return Todo::whereNull('category_id')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Todo'yu başka kategoriye taşı
     */
    public function moveTodoToCategory(int $todoId, ?int $categoryId): ?Todo
    {
        $todo = Todo::find($todoId);
        if (!$todo) {
            throw new \Exception('Todo bulunamadı!');
        }

        // Kategori kontrolü (null olabilir - kategorisiz yapmak için)
        if ($categoryId !== null) {
            $category = Category::find($categoryId);
            if (!$category) {
                throw new \Exception('Hedef kategori bulunamadı!');
            }
            if (!$category->is_active) {
                throw new \Exception('Pasif kategoriye todo taşınamaz!');
            }
        }

        $todo->update(['category_id' => $categoryId]);
        return $todo;
    }

    /**
     * Gelişmiş istatistikler
     */
    public function getTodoStats(): array
    {
        $all = Todo::count();
        $completed = Todo::where('completed', true)->count();
        $pending = Todo::where('completed', false)->count();
        $uncategorized = Todo::whereNull('category_id')->count();

        return [
            'total' => $all,
            'completed' => $completed,
            'pending' => $pending,
            'uncategorized' => $uncategorized,
            'completion_rate' => $all > 0 ?
                round(($completed / $all) * 100, 2) : 0,
            'categories_count' => Category::where('is_active', true)->count()
        ];
    }
}