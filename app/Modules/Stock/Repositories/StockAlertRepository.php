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

    public function getAlerts(array $filters = []): Collection
    {
        return $this->applyFilters($this->model->with(['stock', 'clinic']), $filters)
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

    public function getActiveAlerts(array $filters = []): Collection
    {
        $query = $this->model->active()->with(['stock', 'clinic']);
        return $this->applyFilters($query, $filters)
                    ->orderByDesc('created_at')
                    ->get();
    }

    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
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

    public function deleteActiveAlerts(int $stockId): void
    {
        $this->model->where('stock_id', $stockId)
                   ->delete();
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