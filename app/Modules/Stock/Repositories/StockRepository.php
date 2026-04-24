<?php
// app/Modules/Stock/Repositories/StockRepository.php - TAM DOSYA

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Repositories\Interfaces\StockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    protected $model;

    public function __construct(Stock $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->with(['supplier', 'clinic'])->orderBy('name')->get();
    }

    public function find(int $id): ?Stock
    {
        return $this->model->with(['supplier', 'clinic', 'alerts'])->find($id);
    }

    /**
     * Satırı kilitlererek bul (Pessimistic Locking).
     * Sadece DB::transaction() bloğu içinde kullanılmalıdır.
     * NOT: SQLite desteklemez. Production'da MySQL/PostgreSQL gerektirir.
     */
    public function findAndLock(int $id): ?Stock
    {
        return $this->model->with(['supplier', 'clinic'])
                           ->lockForUpdate()
                           ->find($id);
    }

    public function create(array $data): Stock
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Stock
    {
        $stock = $this->find($id);
        if ($stock) {
            $stock->update($data);
            return $stock;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $stock = $this->find($id);
        return $stock ? $stock->delete() : false;
    }

    public function forceDelete(int $id): bool
    {
        $stock = $this->model->withTrashed()->find($id);
        return $stock ? $stock->forceDelete() : false;
    }

    public function getAllWithFilters(array $filters): Collection
    {
        $query = $this->model->with(['supplier', 'clinic']);

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['stock_status']) || !empty($filters['level'])) {
            $statusFilter = $filters['stock_status'] ?? $filters['level'];
            switch ($statusFilter) {
                case 'low':
                    $query->lowStock();
                    break;
                case 'critical':
                    $query->criticalStock();
                    break;
                case 'expired':
                    $query->expired();
                    break;
            }
        }

        if (!empty($filters['search']) || !empty($filters['name'])) {
            $search = '%' . ($filters['search'] ?? $filters['name']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search)
                  ->orWhere('brand', 'like', $search);
            });
        }

        if (!empty($filters['expiry_filter'])) {
            switch ($filters['expiry_filter']) {
                case 'expired':
                    $query->expired();
                    break;
                case 'expiring_soon':
                    $query->nearExpiry();
                    break;
            }
        }

        return $query->orderBy('name')->get();
    }

    public function getLowStockItems(int $clinicId = null): Collection
    {
        $query = $this->model->lowStock()->with(['supplier', 'clinic']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('current_stock')->get();
    }

    public function getCriticalStockItems(int $clinicId = null): Collection
    {
        $query = $this->model->criticalStock()->with(['supplier', 'clinic']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('current_stock')->get();
    }

    public function getExpiringItems(int $days = 30, int $clinicId = null): Collection
    {
        $query = $this->model->nearExpiry($days)->with(['supplier', 'clinic']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('expiry_date')->get();
    }

    public function getExpiredItems(int $clinicId = null): Collection
    {
        $query = $this->model->expired()->with(['supplier', 'clinic']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('expiry_date')->get();
    }

    public function findByClinicAndProduct(int $clinicId, string $name, string $brand = null): ?Stock
    {
        $query = $this->model->where('clinic_id', $clinicId)->where('name', $name);

        if ($brand) {
            $query->where('brand', $brand);
        }

        return $query->first();
    }
    public function findByCode(string $code): ?Stock
    {
        return $this->model->where('code', $code)->first();
    }

    public function getNextSequenceNumber(int $clinicId): int
    {
        return $this->model->where('clinic_id', $clinicId)->count() + 1;
    }

    public function getBaseQuery()
    {
        return $this->model->newQuery();
    }
}