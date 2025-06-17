<?php

// ==============================================
// 4. StockRequestRepositoryInterface
// app/Modules/Stock/Repositories/Interfaces/StockRequestRepositoryInterface.php
// ==============================================

namespace App\Modules\Stock\Repositories\Interfaces;

use App\Modules\Stock\Models\StockRequest;
use Illuminate\Database\Eloquent\Collection;

interface StockRequestRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?StockRequest;
    public function create(array $data): StockRequest;
    public function update(int $id, array $data): ?StockRequest;
    public function delete(int $id): bool;
    public function getPendingRequests(int $clinicId = null): Collection;
    public function getRequestsByClinic(int $clinicId, string $type = 'all'): Collection;
    public function getRequestsByStatus(string $status): Collection;
}