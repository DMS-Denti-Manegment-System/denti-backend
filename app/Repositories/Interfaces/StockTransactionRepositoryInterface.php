<?php

// ==============================================
// 5. StockTransactionRepositoryInterface
// app/Modules/Stock/Repositories/Interfaces/StockTransactionRepositoryInterface.php
// ==============================================

namespace App\Repositories\Interfaces;

use App\Models\StockTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface StockTransactionRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?StockTransaction;

    public function create(array $data): StockTransaction;

    public function getByStock(int $stockId): Collection;

    public function getByClinic(int $clinicId): Collection;

    public function getByDateRange(Carbon $startDate, Carbon $endDate, ?int $clinicId = null): Collection;

    public function getByType(string $type, ?int $clinicId = null): Collection;

    public function getBaseQuery();
}
