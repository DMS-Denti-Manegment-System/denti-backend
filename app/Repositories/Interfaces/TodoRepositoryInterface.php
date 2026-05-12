<?php

// app/Repositories/Interfaces/TodoRepositoryInterface.php

namespace App\Repositories\Interfaces;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;

interface TodoRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Todo;

    public function create(array $data): Todo;

    public function update(int $id, array $data): ?Todo;

    public function delete(int $id): bool;

    public function getCompleted(): Collection;

    public function getPending(): Collection;

    public function getByCategory(int $categoryId): Collection;

    public function getWithCategory(): Collection;

    public function getAllWithFilters(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
}
