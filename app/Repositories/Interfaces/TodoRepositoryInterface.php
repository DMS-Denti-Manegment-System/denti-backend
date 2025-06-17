<?php
// app/Repositories/Interfaces/TodoRepositoryInterface.php - GÜNCELLENMİŞ

namespace App\Repositories\Interfaces;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;

interface TodoRepositoryInterface
{
    // Mevcut methodlar
    public function all(): Collection;
    public function find(int $id): ?Todo;
    public function create(array $data): Todo;
    public function update(int $id, array $data): ?Todo;
    public function delete(int $id): bool;
    public function getCompleted(): Collection;
    public function getPending(): Collection;

    // YENİ EKLENEN METHODLAR
    public function getByCategory(int $categoryId): Collection;
    public function getTodosWithCategories(): Collection;
    public function getUncategorizedTodos(): Collection;
}