<?php

// ==============================================
// 3. ClinicRepository
// app/Modules/Stock/Repositories/ClinicRepository.php
// ==============================================

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\Clinic;
use App\Modules\Stock\Repositories\Interfaces\ClinicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ClinicRepository implements ClinicRepositoryInterface
{
    protected $model;

    public function __construct(Clinic $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->orderBy('name')->get();
    }

    public function getAllWithFilters(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($filters['search']) || !empty($filters['name'])) {
            $search = '%' . ($filters['search'] ?? $filters['name']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('city', 'like', $search);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') $query->where('is_active', true);
            if ($filters['status'] === 'inactive') $query->where('is_active', false);
        }

        return $query->orderBy('name')->get();
    }

    public function find(int $id): ?Clinic
    {
        return $this->model->with(['stocks'])->find($id);
    }

    public function create(array $data): Clinic
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Clinic
    {
        $clinic = $this->find($id);
        if ($clinic) {
            $clinic->update($data);
            return $clinic;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $clinic = $this->find($id);
        if ($clinic && $clinic->stocks()->count() === 0) {
            return $clinic->delete();
        }
        return false;
    }

    public function getActive(): Collection
    {
        return $this->model->active()->orderBy('name')->get();
    }


    public function getStockSummary(int $clinicId): array
    {
        $summary = DB::table('stocks')
            ->where('clinic_id', $clinicId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(current_stock) as total_quantity,
                SUM(current_stock * purchase_price) as total_value,
                SUM(CASE WHEN current_stock <= yellow_alert_level THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE WHEN current_stock <= red_alert_level THEN 1 ELSE 0 END) as critical_stock_items
            ')
            ->first();

        return [
            'total_items' => $summary->total_items ?? 0,
            'total_quantity' => $summary->total_quantity ?? 0,
            'total_value' => round($summary->total_value ?? 0, 2),
            'low_stock_items' => $summary->low_stock_items ?? 0,
            'critical_stock_items' => $summary->critical_stock_items ?? 0,
        ];
    }

    public function getGlobalStats(): array
    {
        $totalClinics = $this->model->count();
        $activeClinics = $this->model->where('is_active', true)->count();
        
        $stockStats = DB::table('stocks')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(CASE WHEN current_stock <= yellow_alert_level THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE WHEN current_stock <= red_alert_level THEN 1 ELSE 0 END) as critical_stock_items
            ')
            ->first();

        return [
            'total_clinics' => $totalClinics,
            'active_clinics' => $activeClinics,
            'total_stock_items' => $stockStats->total_items ?? 0,
            'low_stock_items' => $stockStats->low_stock_items ?? 0,
            'critical_stock_items' => $stockStats->critical_stock_items ?? 0,
        ];
    }
}