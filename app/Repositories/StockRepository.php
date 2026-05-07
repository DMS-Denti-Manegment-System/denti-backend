<?php

// app/Modules/Stock/Repositories/StockRepository.php - TAM DOSYA

namespace App\Repositories;

use App\Models\Stock;
use App\Repositories\Interfaces\StockRepositoryInterface;
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
        return $this->model->with(['product', 'supplier', 'clinic'])->latest()->get();
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

    public function getAllWithFilters(array $filters, int $perPage = 50)
    {
        $query = $this->model->with([
            'product:id,name,sku,unit,category,brand',
            'supplier:id,name',
            'clinic:id,name',
            'alerts',
        ]);

        if (! empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $hasProductJoin = false;
        if (! empty($filters['stock_status']) || ! empty($filters['level'])) {
            $statusFilter = $filters['stock_status'] ?? $filters['level'];
            switch ($statusFilter) {
                case 'low':
                    $query->lowStock();
                    $hasProductJoin = true;
                    break;
                case 'critical':
                    $query->criticalStock();
                    $hasProductJoin = true;
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'near_expiry':
                    $query->nearExpiry();
                    break;
            }
        }

        if (! empty($filters['search']) || ! empty($filters['name'])) {
            $search = '%'.($filters['search'] ?? $filters['name']).'%';
            if (! $hasProductJoin) {
                $query->join('products', 'stocks.product_id', '=', 'products.id');
            }
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', $search)
                    ->orWhere('products.sku', 'like', $search)
                    ->orWhere('products.brand', 'like', $search);
            })->select('stocks.*');
        }

        if (! empty($filters['expiry_filter'])) {
            switch ($filters['expiry_filter']) {
                case 'expired':
                    $query->expired();
                    break;
                case 'expiring_soon':
                    $query->nearExpiry();
                    break;
            }
        }

        return $query->latest()->paginate($perPage);
    }

    public function getLowStockItems(?int $clinicId = null): Collection
    {
        $query = $this->model->lowStock()->with(['supplier', 'clinic', 'product']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('current_stock')->get();
    }

    public function getCriticalStockItems(?int $clinicId = null): Collection
    {
        $query = $this->model->criticalStock()->with(['supplier', 'clinic', 'product']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('current_stock')->get();
    }

    public function getExpiringItems(int $days = 30, ?int $clinicId = null): Collection
    {
        $query = $this->model->nearExpiry($days)->with(['supplier', 'clinic', 'product']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('expiry_date')->get();
    }

    public function getExpiredItems(?int $clinicId = null): Collection
    {
        $query = $this->model->expired()->with(['supplier', 'clinic', 'product']);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('expiry_date')->get();
    }

    public function findByClinicAndProduct(int $clinicId, string $name, ?string $brand = null): ?Stock
    {
        return $this->model->with('product')->where('clinic_id', $clinicId)
            ->whereHas('product', function ($q) use ($name, $brand) {
                $q->where('name', $name);
                if ($brand) {
                    $q->where('brand', $brand);
                }
            })->first();
    }

    public function findByCode(string $code): ?Stock
    {
        return $this->model->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('products.sku', $code)
            ->select('stocks.*')
            ->first();
    }

    public function getNextSequenceNumber(int $clinicId): int
    {
        return $this->model->where('clinic_id', $clinicId)->count() + 1;
    }

    public function getBaseQuery()
    {
        return $this->model->newQuery();
    }

    public function getTransactions(int $stockId): Collection
    {
        $stock = $this->find($stockId);
        if (! $stock) {
            return new Collection;
        }

        return $stock->transactions()->with(['user', 'clinic'])->orderBy('transaction_date', 'desc')->get();
    }
}
