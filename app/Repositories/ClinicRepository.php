<?php

// ==============================================
// 3. ClinicRepository
// app/Modules/Stock/Repositories/ClinicRepository.php
// ==============================================

namespace App\Repositories;

use App\Models\Clinic;
use App\Models\Stock;
use App\Repositories\Interfaces\ClinicRepositoryInterface;
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

    public function getAllWithFilters(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('responsible_person', 'like', $search)
                    ->orWhere('city', 'like', $search);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->latest()->paginate($perPage);
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
        if (! $clinic) {
            return false;
        }

        return DB::transaction(function () use ($clinic) {
            // Klinik silindiğinde stoklarını da pasif/silinmiş yapalım
            $clinic->stocks()->delete(); // Soft delete stocks if they use it

            return $clinic->delete();
        });
    }

    public function getActive(): Collection
    {
        return $this->model->active()->orderBy('name')->get();
    }

    public function getStockSummary(int $clinicId): array
    {
        $totalUnitsRaw = Stock::totalBaseUnitsRaw();
        $summary = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.clinic_id', $clinicId)
            ->where('stocks.is_active', true)
            ->whereNull('stocks.deleted_at')
            ->selectRaw("
                COUNT(*) as total_items,
                SUM({$totalUnitsRaw}) as total_quantity,
                SUM(stocks.current_stock * stocks.purchase_price) as total_value,
                SUM(CASE
                    WHEN {$totalUnitsRaw} <= COALESCE(products.yellow_alert_level, products.min_stock_level, 10)
                    AND ({$totalUnitsRaw} > COALESCE(products.red_alert_level, products.critical_stock_level, 5)
                        OR ({$totalUnitsRaw} = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0))
                    THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE
                    WHEN {$totalUnitsRaw} <= COALESCE(products.red_alert_level, products.critical_stock_level, 5)
                    AND NOT ({$totalUnitsRaw} = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0)
                    THEN 1 ELSE 0 END) as critical_stock_items
            ")
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
        $totalUnitsRaw = Stock::totalBaseUnitsRaw();

        $stockStats = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.is_active', true)
            ->whereNull('stocks.deleted_at')
            ->selectRaw("
                COUNT(*) as total_items,
                SUM(CASE
                    WHEN {$totalUnitsRaw} <= COALESCE(products.yellow_alert_level, products.min_stock_level, 10)
                    AND ({$totalUnitsRaw} > COALESCE(products.red_alert_level, products.critical_stock_level, 5)
                        OR ({$totalUnitsRaw} = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0))
                    THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE
                    WHEN {$totalUnitsRaw} <= COALESCE(products.red_alert_level, products.critical_stock_level, 5)
                    AND NOT ({$totalUnitsRaw} = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0)
                    THEN 1 ELSE 0 END) as critical_stock_items
            ")
            ->first();

        return [
            'total_clinics' => $totalClinics,
            'active_clinics' => $activeClinics,
            'total_stock_items' => $stockStats->total_items ?? 0,
            'low_stock_items' => $stockStats->low_stock_items ?? 0,
            'critical_stock_items' => $stockStats->critical_stock_items ?? 0,
        ];
    }
    public function getClinicStats(): array
    {
        $stats = DB::table('clinics')
            ->whereNull('deleted_at')
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when is_active = true then 1 end) as active')
            ->selectRaw('count(case when is_active = false then 1 end) as passive')
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'active' => (int) ($stats->active ?? 0),
            'passive' => (int) ($stats->passive ?? 0),
        ];
    }
}
