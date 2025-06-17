<?php

// ==============================================
// 3. StockTransactionService.php
// app/Modules/Stock/Services/StockTransactionService.php
// ==============================================

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\StockTransactionRepositoryInterface;
use App\Modules\Stock\Models\StockTransaction;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class StockTransactionService
{
    protected $stockTransactionRepository;

    public function __construct(StockTransactionRepositoryInterface $stockTransactionRepository)
    {
        $this->stockTransactionRepository = $stockTransactionRepository;
    }

    public function getAllTransactions(): Collection
    {
        return $this->stockTransactionRepository->all();
    }

    public function getTransactionById(int $id): ?StockTransaction
    {
        return $this->stockTransactionRepository->find($id);
    }

    public function createTransaction(array $data): StockTransaction
    {
        return $this->stockTransactionRepository->create($data);
    }

    public function getTransactionsByStock(int $stockId): Collection
    {
        return $this->stockTransactionRepository->getByStock($stockId);
    }

    public function getTransactionsByClinic(int $clinicId): Collection
    {
        return $this->stockTransactionRepository->getByClinic($clinicId);
    }

    public function getTransactionsByDateRange(Carbon $startDate, Carbon $endDate, int $clinicId = null): Collection
    {
        return $this->stockTransactionRepository->getByDateRange($startDate, $endDate, $clinicId);
    }

    public function getTransactionsByType(string $type, int $clinicId = null): Collection
    {
        return $this->stockTransactionRepository->getByType($type, $clinicId);
    }
}