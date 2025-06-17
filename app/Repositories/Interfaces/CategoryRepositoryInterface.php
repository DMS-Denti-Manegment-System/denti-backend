<?php

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Temel CRUD operasyonları
     */
    public function all(): Collection;
    public function find(int $id): ?Category;
    public function create(array $data): Category;
    public function update(int $id, array $data): ?Category;
    public function delete(int $id): bool;

    /**
     * Kategori-spesifik methodlar
     */
    public function getActiveCategories(): Collection;
    public function getCategoryWithTodos(int $id): ?Category;
    public function getCategoriesWithTodoCount(): Collection;
    public function searchByName(string $name): Collection;
}