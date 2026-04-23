<?php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\StockRepositoryInterface;
use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Jobs\CheckStockLevelsJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class StockService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository,
        protected StockCalculatorService $calculatorService,
        protected StockTransactionService $transactionService,
        protected StockAlertService $stockAlertService
    ) {}

    public function getAllStocks(array $filters = []): Collection
    {
        return $this->stockRepository->getAllWithFilters($filters);
    }

    public function getStockById(int $id): ?Stock
    {
        return $this->stockRepository->find($id);
    }

    public function updateStock(int $id, array $data): ?Stock
    {
        return DB::transaction(function () use ($id, $data) {
            $stock = $this->stockRepository->find($id);
            if (!$stock) return null;

            if (isset($data['current_stock']) || isset($data['reserved_stock'])) {
                $currentStock = $data['current_stock'] ?? $stock->current_stock;
                $reservedStock = $data['reserved_stock'] ?? $stock->reserved_stock;
                $data['available_stock'] = $currentStock - $reservedStock;
            }

            $updatedStock = $this->stockRepository->update($id, $data);

            if ($updatedStock) {
                $this->checkStockLevels($updatedStock);
                $this->clearStockCache($updatedStock->company_id, $updatedStock->clinic_id);
            }

            return $updatedStock;
        });
    }

    public function adjustStock(int $stockId, int $quantity, string $reason, string $performedBy, bool $isSubUnit = false): bool
    {
        return DB::transaction(function () use ($stockId, $quantity, $reason, $performedBy, $isSubUnit) {
            $stock = $this->stockRepository->find($stockId);
            if (!$stock) return false;

            $previousTotal = $stock->total_base_units;
            
            if ($isSubUnit && $stock->has_sub_unit && $stock->sub_unit_multiplier > 0) {
                $newLevels = $this->calculatorService->calculateAdjustment(
                    $stock->current_stock,
                    $stock->current_sub_stock,
                    $quantity,
                    $stock->sub_unit_multiplier
                );

                $this->stockRepository->update($stockId, array_merge($newLevels, [
                    'available_stock' => $newLevels['current_stock'] - $stock->reserved_stock
                ]));
            } else {
                $newStock = $stock->current_stock + $quantity;
                if ($newStock < 0) return false;

                $this->stockRepository->update($stockId, [
                    'current_stock' => $newStock,
                    'available_stock' => $newStock - $stock->reserved_stock
                ]);
            }

            $freshStock = $stock->fresh();

            $this->createTransaction([
                'stock_id' => $stockId,
                'clinic_id' => $stock->clinic_id,
                'type' => 'adjustment',
                'quantity' => abs($quantity),
                'previous_stock' => $previousTotal,
                'new_stock' => $freshStock->total_base_units,
                'description' => ($isSubUnit ? "Alt Birim Düzeltme: " : "Ana Birim Düzeltme: ") . $reason,
                'performed_by' => $performedBy,
                'transaction_date' => now(),
                'is_sub_unit' => $isSubUnit
            ]);

            $this->checkStockLevels($freshStock);
            $this->clearStockCache($freshStock->company_id, $freshStock->clinic_id);

            return true;
        });
    }

    public function useStock(int $stockId, int $quantity, string $performedBy, string $notes = null): bool
    {
        return DB::transaction(function () use ($stockId, $quantity, $performedBy, $notes) {
            $stock = $this->stockRepository->find($stockId);
            if (!$stock) return false;

            $isSubUnitUsage = $stock->has_sub_unit && $stock->sub_unit_multiplier > 0;
            $previousTotal = $isSubUnitUsage ? $stock->total_base_units : $stock->current_stock;

            if ($isSubUnitUsage) {
                $newLevels = $this->calculatorService->calculateUsage(
                    $stock->current_stock,
                    $stock->current_sub_stock,
                    $quantity,
                    $stock->sub_unit_multiplier
                );

                if (!$newLevels) return false;

                $updateData = array_merge($newLevels, [
                    'available_stock' => $newLevels['current_stock'] - $stock->reserved_stock,
                    'internal_usage_count' => $stock->internal_usage_count + $quantity
                ]);
            } else {
                if ($stock->available_stock < $quantity) return false;
                
                $newMainStock = $stock->current_stock - $quantity;
                $updateData = [
                    'current_stock' => $newMainStock,
                    'available_stock' => $newMainStock - $stock->reserved_stock,
                    'internal_usage_count' => $stock->internal_usage_count + $quantity
                ];
            }

            $this->stockRepository->update($stockId, $updateData);
            $freshStock = $stock->fresh();

            $this->createTransaction([
                'stock_id' => $stockId,
                'clinic_id' => $stock->clinic_id,
                'type' => 'usage',
                'quantity' => $quantity,
                'previous_stock' => $previousTotal,
                'new_stock' => $isSubUnitUsage ? $freshStock->total_base_units : $freshStock->current_stock,
                'notes' => $notes,
                'performed_by' => $performedBy,
                'transaction_date' => now(),
                'is_sub_unit' => $isSubUnitUsage
            ]);

            $this->checkStockLevels($freshStock);
            $this->clearStockCache($freshStock->company_id, $freshStock->clinic_id);

            return true;
        });
    }

    public function createStock(array $data): Stock
    {
        return DB::transaction(function () use ($data) {
            $data['available_stock'] = ($data['current_stock'] ?? 0) - ($data['reserved_stock'] ?? 0);

            $stock = $this->stockRepository->create($data);

            $this->checkStockLevels($stock);
            $this->clearStockCache($stock->company_id, $stock->clinic_id);

            return $stock;
        });
    }

    public function deleteStock(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $stock = $this->stockRepository->find($id);
            if (!$stock) return false;

            $companyId = $stock->company_id;
            $clinicId = $stock->clinic_id;

            $stock->alerts()->delete();
            $result = $this->stockRepository->delete($id);
            
            if ($result) {
                $this->clearStockCache($companyId, $clinicId);
            }

            return $result;
        });
    }

    public function getStockStats(int $clinicId = null): array
    {
        $companyId = Auth::user()->company_id;
        $cacheKey = "stock_stats_{$companyId}_" . ($clinicId ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($clinicId) {
            $baseQuery = $this->stockRepository->getBaseQuery();
            if ($clinicId) $baseQuery->where('clinic_id', $clinicId);

            $nearExpiryLimit = now()->addDays(30)->toDateTimeString();
            $now = now()->toDateTimeString();

            $stats = $baseQuery->selectRaw("
                COUNT(*) as total_items,
                SUM(CASE WHEN is_active = 1 AND (CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) <= COALESCE(yellow_alert_level, min_stock_level) THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE WHEN is_active = 1 AND (CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) <= COALESCE(red_alert_level, critical_stock_level) THEN 1 ELSE 0 END) as critical_stock_items,
                SUM(CASE WHEN is_active = 1 AND track_expiry = 1 AND expiry_date <= ? AND expiry_date > ? THEN 1 ELSE 0 END) as expiring_items,
                SUM(purchase_price * current_stock) as total_value
            ", [$nearExpiryLimit, $now])->first();

            return [
                'total_items' => (int) ($stats->total_items ?? 0),
                'low_stock_items' => (int) ($stats->low_stock_items ?? 0),
                'critical_stock_items' => (int) ($stats->critical_stock_items ?? 0),
                'expiring_items' => (int) ($stats->expiring_items ?? 0),
                'total_value' => round((float) ($stats->total_value ?? 0), 2)
            ];
        });
    }

    protected function clearStockCache(int $companyId, int $clinicId): void
    {
        Cache::forget("stock_stats_{$companyId}_all");
        Cache::forget("stock_stats_{$companyId}_{$clinicId}");
    }

    protected function checkStockLevels(Stock $stock): void
    {
        $this->stockAlertService->checkAndCreateAlerts($stock);
    }

    protected function createTransaction(array $data): void
    {
        $data['transaction_number'] = $this->generateTransactionNumber();
        $this->transactionService->createTransaction($data);
    }

    protected function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = DB::table('stock_transactions')
                     ->whereDate('created_at', now())
                     ->count() + 1;

        return 'TXN-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
