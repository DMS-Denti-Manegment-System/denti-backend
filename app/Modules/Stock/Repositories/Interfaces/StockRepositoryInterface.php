<?php

namespace App\Modules\Stock\Repositories\Interfaces;

use App\Modules\Stock\Models\Stock;
use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Stock;
    public function create(array $data): Stock;
    public function update(int $id, array $data): ?Stock;
    public function delete(int $id): bool;
    public function getAllWithFilters(array $filters): Collection;
    public function getLowStockItems(int $clinicId = null): Collection;
    public function getCriticalStockItems(int $clinicId = null): Collection;
    public function getExpiringItems(int $days = 30, int $clinicId = null): Collection;
    public function getExpiredItems(int $clinicId = null): Collection;
    public function findByClinicAndProduct(int $clinicId, string $name, string $brand = null): ?Stock;
    public function findByCode(string $code): ?Stock;
    public function getNextSequenceNumber(int $clinicId): int;
    public function getBaseQuery();
}