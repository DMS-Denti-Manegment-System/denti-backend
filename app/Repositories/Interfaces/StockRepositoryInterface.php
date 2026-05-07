<?php

namespace App\Repositories\Interfaces;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Stock;

    public function findAndLock(int $id): ?Stock; // Pessimistic locking için (DB::transaction içinde kullanılmalı)

    public function create(array $data): Stock;

    public function update(int $id, array $data): ?Stock;

    public function delete(int $id): bool;

    public function forceDelete(int $id): bool;

    public function getAllWithFilters(array $filters, int $perPage = 50);

    public function getLowStockItems(?int $clinicId = null): Collection;

    public function getCriticalStockItems(?int $clinicId = null): Collection;

    public function getExpiringItems(int $days = 30, ?int $clinicId = null): Collection;

    public function getExpiredItems(?int $clinicId = null): Collection;

    public function findByClinicAndProduct(int $clinicId, string $name, ?string $brand = null): ?Stock;

    public function findByCode(string $code): ?Stock;

    public function getNextSequenceNumber(int $clinicId): int;

    public function getBaseQuery();

    public function getTransactions(int $stockId): Collection;
}
