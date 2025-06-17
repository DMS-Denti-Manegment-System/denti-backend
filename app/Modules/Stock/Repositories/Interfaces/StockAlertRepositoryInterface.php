<?php

// ==============================================
// 6. StockAlertRepositoryInterface
// app/Modules/Stock/Repositories/Interfaces/StockAlertRepositoryInterface.php
// ==============================================

namespace App\Modules\Stock\Repositories\Interfaces;

use App\Modules\Stock\Models\StockAlert;
use Illuminate\Database\Eloquent\Collection;

interface StockAlertRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?StockAlert;
    public function create(array $data): StockAlert;
    public function update(int $id, array $data): ?StockAlert;
    public function delete(int $id): bool;
    public function getActiveAlerts(int $clinicId = null, string $type = null): Collection;
    public function resolveActiveAlerts(int $stockId): void;
    public function countActiveAlerts(int $clinicId = null): int;
    public function countAlertsByType(string $type, int $clinicId = null): int;
}