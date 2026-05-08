<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function getAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $totalBaseUnitsSql = Stock::totalBaseUnitsRaw();

        $stockSummary = Stock::query()
            ->selectRaw('product_id')
            ->selectRaw("SUM(CASE WHEN is_active = 1 THEN {$totalBaseUnitsSql} ELSE 0 END) as total_stock")
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as batches_count')
            ->groupBy('product_id');

        $query = Product::query()
            ->with([
                'clinic:id,name',
                'batches' => fn ($q) => $q->with(['supplier:id,name', 'clinic:id,name'])->latest('id'),
            ])
            ->leftJoinSub($stockSummary, 'stock_summary', function ($join) {
                $join->on('stock_summary.product_id', '=', 'products.id');
            })
            ->select('products.*')
            ->selectRaw('COALESCE(stock_summary.total_stock, 0) as total_stock')
            ->selectRaw('COALESCE(stock_summary.batches_count, 0) as batches_count');

        $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
        $now = now();

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
                    ->orWhereHas('batches', function ($batchQuery) use ($clinicId) {
                        $batchQuery->where('clinic_id', $clinicId);
                    });
            });
        }

        // 4. Durum (Aktif/Pasif)
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        // 5. Seviye (Stok ve SKT Uyarıları)
        if (! empty($filters['level'])) {
            $level = $filters['level'];
            $levelAliases = [
                'low_stock' => 'low',
                'critical_stock' => 'critical',
                'low_expiry' => 'near_expiry',
            ];
            $level = $levelAliases[$level] ?? $level;

            // Stok Miktarı Bazlı Filtreler (Total Stock)
            if (in_array($level, ['low', 'critical'], true)) {
                if ($level === 'critical') {
                    $query->whereHas('batches', function ($q) {
                        $q->where('is_active', 1);
                    })->whereRaw('COALESCE(stock_summary.total_stock, 0) <= COALESCE(products.red_alert_level, products.critical_stock_level)')
                        ->whereRaw('NOT (COALESCE(stock_summary.total_stock, 0) = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0)');
                } else {
                    $query->whereRaw('COALESCE(stock_summary.total_stock, 0) <= COALESCE(products.yellow_alert_level, products.min_stock_level)')
                        ->whereRaw('(COALESCE(stock_summary.total_stock, 0) > COALESCE(products.red_alert_level, products.critical_stock_level) OR (COALESCE(stock_summary.total_stock, 0) = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0))');
                }
            }

            // SKT (Miyat) Bazlı Filtreler
            if (in_array($level, ['near_expiry', 'critical_expiry', 'expired'], true)) {
                $query->whereHas('batches', function ($q) use ($level, $isSqlite, $now) {
                    $q->where('is_active', 1)->where('track_expiry', 1);

                    if ($level === 'expired') {
                        $q->whereDate('expiry_date', '<', $now->toDateString());
                    } elseif ($level === 'critical_expiry') {
                        $redDaysSql = $isSqlite
                            ? "date(?, '+' || COALESCE(expiry_red_days, 15) || ' days')"
                            : 'DATE_ADD(?, INTERVAL COALESCE(expiry_red_days, 15) DAY)';
                        $q->whereDate('expiry_date', '>=', $now->toDateString())
                            ->whereRaw("expiry_date <= {$redDaysSql}", [$now->toDateTimeString()]);
                    } else { // near_expiry
                        $yellowDaysSql = $isSqlite
                            ? "date(?, '+' || COALESCE(expiry_yellow_days, 30) || ' days')"
                            : 'DATE_ADD(?, INTERVAL COALESCE(expiry_yellow_days, 30) DAY)';
                        $redDaysSql = $isSqlite
                            ? "date(?, '+' || COALESCE(expiry_red_days, 15) || ' days')"
                            : 'DATE_ADD(?, INTERVAL COALESCE(expiry_red_days, 15) DAY)';
                        $q->whereDate('expiry_date', '>=', $now->toDateString())
                            ->whereRaw("expiry_date <= {$yellowDaysSql}", [$now->toDateTimeString()])
                            ->whereRaw("expiry_date > {$redDaysSql}", [$now->toDateTimeString()]);
                    }
                });
            }
        }

        return $query->latest()->paginate($perPage);
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
