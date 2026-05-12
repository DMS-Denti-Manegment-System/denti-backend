<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function getAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $clinicId = $filters['clinic_id'] ?? null;

        $query = Product::query()
            ->with([
                'clinic:id,name',
                'latestBatch' => fn ($batchQuery) => $batchQuery->select([
                    'stocks.id', 'stocks.product_id', 'stocks.clinic_id', 'stocks.storage_location',
                ]),
                'latestBatch.clinic:id,name',
            ])
            ->joinStockSummary($clinicId)
            ->select('products.*')
            ->selectRaw('COALESCE(stock_summary.total_stock, 0) as total_stock')
            ->selectRaw('COALESCE(stock_summary.batches_count, 0) as batches_count');

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Filtreleri sorguya uygular.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // 1. Arama (İsim veya SKU)
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search);
            });
        }

        // 2. Kategori
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // 3. Klinik Filtresi
        if (! empty($filters['clinic_id'])) {
            $clinicId = $filters['clinic_id'];
            $query->where(function ($q) use ($clinicId) {
                $q->where('clinic_id', $clinicId)
                    ->orWhereHas('batches', fn ($q) => $q->where('clinic_id', $clinicId));
            });
        }

        // 4. Durum (Aktif/Pasif)
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        // 5. Seviye (Stok ve SKT Uyarıları)
        if (! empty($filters['level'])) {
            $this->applyLevelFilter($query, $filters['level']);
        }
    }

    /**
     * Kritik/Düşük stok veya SKT filtrelerini uygular.
     */
    protected function applyLevelFilter(Builder $query, string $level): void
    {
        $levelAliases = ['low_stock' => 'low', 'critical_stock' => 'critical', 'low_expiry' => 'near_expiry'];
        $level = $levelAliases[$level] ?? $level;

        // Stok Miktarı Bazlı Filtreler
        if ($level === 'critical') {
            $query->whereRaw('COALESCE(stock_summary.total_stock, 0) <= COALESCE(products.red_alert_level, products.critical_stock_level)')
                ->whereRaw('NOT (COALESCE(stock_summary.total_stock, 0) = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0)');
        } elseif ($level === 'low') {
            $query->whereRaw('COALESCE(stock_summary.total_stock, 0) <= COALESCE(products.yellow_alert_level, products.min_stock_level)')
                ->whereRaw('(COALESCE(stock_summary.total_stock, 0) > COALESCE(products.red_alert_level, products.critical_stock_level) OR (COALESCE(stock_summary.total_stock, 0) = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0))');
        }

        // SKT (Miyat) Bazlı Filtreler
        if (in_array($level, ['near_expiry', 'critical_expiry', 'expired'], true)) {
            $query->whereHas('batches', function ($q) use ($level) {
                $q->where('is_active', 1)->where('track_expiry', 1);
                $now = now();

                if ($level === 'expired') {
                    $q->whereDate('expiry_date', '<', $now->toDateString());
                } elseif ($level === 'critical_expiry') {
                    $q->whereDate('expiry_date', '>=', $now->toDateString())
                        ->whereRaw('expiry_date <= '.$this->getDateAddSql('expiry_red_days', 15), [$now->toDateTimeString()]);
                } else { // near_expiry
                    $q->whereDate('expiry_date', '>=', $now->toDateString())
                        ->whereRaw('expiry_date <= '.$this->getDateAddSql('expiry_yellow_days', 30), [$now->toDateTimeString()])
                        ->whereRaw('expiry_date > '.$this->getDateAddSql('expiry_red_days', 15), [$now->toDateTimeString()]);
                }
            });
        }
    }

    /**
     * SQL Sürücüsüne göre tarih ekleme fonksiyonunu döndürür.
     */
    protected function getDateAddSql(string $column, int $default): string
    {
        return \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite'
            ? "date(?, '+' || COALESCE({$column}, {$default}) || ' days')"
            : "DATE_ADD(?, INTERVAL COALESCE({$column}, {$default}) DAY)";
    }

    public function find(int $id): ?Product
    {
        return Product::with(['batches.supplier', 'batches.clinic', 'clinic'])
            ->withCount('batches')
            ->withSum(['stockTransactions as total_in' => function ($q) {
                $q->whereIn('type', Product::incomingTransactionTypes());
            }], 'quantity')
            ->withSum(['stockTransactions as total_out' => function ($q) {
                $q->whereIn('type', Product::outgoingTransactionTypes());
            }], 'quantity')
            ->find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): ?Product
    {
        $product = Product::find($id);
        if ($product) {
            $product->update($data);

            return $product;
        }

        return null;
    }

    public function delete(int $id): bool
    {
        $product = Product::find($id);
        if ($product) {
            return $product->delete();
        }

        return false;
    }

    public function getTransactions(int $id): Collection
    {
        $stockIds = \App\Models\Stock::where('product_id', $id)->pluck('id')->toArray();

        if (empty($stockIds)) {
            return new Collection;
        }

        return \App\Models\StockTransaction::with(['user', 'stock.product'])
            ->whereIn('stock_id', $stockIds)
            ->orderByDesc('transaction_date')
            ->get();
    }
}
