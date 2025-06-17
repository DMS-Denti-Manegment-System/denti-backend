<?php

// ==============================================
// 5. StockTransactionRepository
// app/Modules/Stock/Repositories/StockTransactionRepository.php
// ==============================================

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\StockTransaction;
use App\Modules\Stock\Repositories\Interfaces\StockTransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class StockTransactionRepository implements StockTransactionRepositoryInterface
{
    protected $model;

    public function __construct(StockTransaction $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->with(['stock', 'clinic'])
                          ->orderByDesc('transaction_date')
                          ->get();
    }

    public function find(int $id): ?StockTransaction
    {
        return $this->model->with(['stock', 'clinic', 'stockRequest'])->find($id);
    }

    public function create(array $data): StockTransaction
    {
        return $this->model->create($data);
    }

    public function getByStock(int $stockId): Collection
    {
        return $this->model->where('stock_id', $stockId)
                          ->with(['clinic'])
                          ->orderByDesc('transaction_date')
                          ->get();
    }

    public function getByClinic(int $clinicId): Collection
    {
        return $this->model->where('clinic_id', $clinicId)
                          ->with(['stock'])
                          ->orderByDesc('transaction_date')
                          ->get();
    }

    public function getByDateRange(Carbon $startDate, Carbon $endDate, int $clinicId = null): Collection
    {
        $query = $this->model->byDateRange($startDate, $endDate)->with(['stock', 'clinic']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderByDesc('transaction_date')->get();
    }

    public function getByType(string $type, int $clinicId = null): Collection
    {
        $query = $this->model->byType($type)->with(['stock', 'clinic']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderByDesc('transaction_date')->get();
    }

    public function getBaseQuery()
    {
        return $this->model->newQuery();
    }
}