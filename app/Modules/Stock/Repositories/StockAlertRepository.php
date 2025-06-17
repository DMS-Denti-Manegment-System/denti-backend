<?php

// ==============================================
// 2. StockAlertRepository.php - DÜZELTİLMİŞ
// app/Modules/Stock/Repositories/StockAlertRepository.php
// ==============================================

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\StockAlert;
use App\Modules\Stock\Repositories\Interfaces\StockAlertRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StockAlertRepository implements StockAlertRepositoryInterface
{
    protected $model;

    public function __construct(StockAlert $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->with(['stock', 'clinic'])
                          ->orderByDesc('created_at')
                          ->get();
    }

    public function find(int $id): ?StockAlert
    {
        return $this->model->with(['stock', 'clinic'])->find($id);
    }

    public function create(array $data): StockAlert
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?StockAlert
    {
        $alert = $this->find($id);
        if ($alert) {
            $alert->update($data);
            return $alert->fresh(['stock', 'clinic']);
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $alert = $this->find($id);
        return $alert ? $alert->delete() : false;
    }

    public function getActiveAlerts(int $clinicId = null, string $type = null): Collection
    {
        $query = $this->model->active()->with(['stock', 'clinic']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        if ($type) {
            $query->byType($type);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function resolveActiveAlerts(int $stockId): void
    {
        $this->model->where('stock_id', $stockId)
                   ->where('is_active', true)
                   ->where('is_resolved', false)
                   ->update([
                       'is_resolved' => true,
                       'resolved_at' => now()
                   ]);
    }

    public function countActiveAlerts(int $clinicId = null): int
    {
        $query = $this->model->active();

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->count();
    }

    public function countAlertsByType(string $type, int $clinicId = null): int
    {
        $query = $this->model->active()->byType($type);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->count();
    }
}