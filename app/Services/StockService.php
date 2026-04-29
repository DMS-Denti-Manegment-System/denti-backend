<?php

namespace App\Services;

use App\Exceptions\Stock\InsufficientStockException;
use App\Exceptions\Stock\StockNotFoundException;
use App\Events\Stock\StockLevelChanged;
use App\Repositories\Interfaces\StockRepositoryInterface;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StockService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository,
        protected StockCalculatorService $calculatorService,
        protected StockTransactionService $transactionService,
        protected StockAlertService $stockAlertService
    ) {}

    public function getAllStocks(array $filters = [], int $perPage = 50)
    {
        return $this->stockRepository->getAllWithFilters($filters, $perPage);
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
                $currentStock  = $data['current_stock']  ?? $stock->current_stock;
                $reservedStock = $data['reserved_stock'] ?? $stock->reserved_stock;
                $data['available_stock'] = $currentStock - $reservedStock;
            }

            $updatedStock = $this->stockRepository->update($id, $data);

            if ($updatedStock) {
                DB::afterCommit(function () use ($updatedStock) {
                    StockLevelChanged::dispatch($updatedStock, $updatedStock->company_id, $updatedStock->clinic_id);
                });
            }

            return $updatedStock;
        });
    }

    /**
     * Stok miktarını manuel olarak ayarlar.
     * Race condition'a karşı pessimistic locking (lockForUpdate) kullanır.
     */
    public function adjustStock(int $stockId, int $delta, string $reason, string $performedBy, bool $isSubUnit = false, string $type = 'increase', int $targetQuantity = 0): bool
    {
        return DB::transaction(function () use ($stockId, $delta, $reason, $performedBy, $isSubUnit, $type, $targetQuantity) {
            $stock = $this->stockRepository->findAndLock($stockId);
            if (!$stock) {
                throw new StockNotFoundException($stockId);
            }

            $previousTotal = $stock->total_base_units;

            // Business Logic: Calculate delta if sync
            if ($type === 'sync') {
                $current = $isSubUnit ? $stock->current_sub_stock : $stock->current_stock;
                $delta = $targetQuantity - $current;
                if ($delta === 0) return true;
            } elseif ($type === 'decrease') {
                $delta = -abs($delta);
            } else {
                $delta = abs($delta);
            }

            if ($isSubUnit && $stock->has_sub_unit && $stock->sub_unit_multiplier > 0) {
                $newLevels = $this->calculatorService->calculateAdjustment(
                    $stock->current_stock,
                    $stock->current_sub_stock,
                    $delta,
                    $stock->sub_unit_multiplier
                );

                if (
                    !$newLevels ||
                    ($newLevels['current_stock'] ?? 0) < 0 ||
                    ($newLevels['current_sub_stock'] ?? 0) < 0
                ) {
                    throw new InsufficientStockException($stock->total_base_units, abs($delta));
                }

                $this->stockRepository->update($stockId, array_merge($newLevels, [
                    'available_stock' => $newLevels['current_stock'] - $stock->reserved_stock
                ]));
            } else {
                $newStock = $stock->current_stock + $delta;
                if ($newStock < 0) {
                    throw new InsufficientStockException($stock->current_stock, abs($delta));
                }

                $this->stockRepository->update($stockId, [
                    'current_stock'   => $newStock,
                    'available_stock' => $newStock - $stock->reserved_stock
                ]);
            }

            $freshStock = $stock->fresh();

            $this->createTransaction([
                'stock_id'         => $stockId,
                'clinic_id'        => $stock->clinic_id,
                'type'             => $delta > 0 ? 'adjustment_increase' : 'adjustment_decrease',
                'quantity'         => abs($delta),
                'previous_stock'   => $previousTotal,
                'new_stock'        => $freshStock->total_base_units,
                'description'      => ($isSubUnit ? 'Alt Birim Düzeltme: ' : 'Ana Birim Düzeltme: ') . $reason,
                'performed_by'     => $performedBy,
                'transaction_date' => now(),
                'is_sub_unit'      => $isSubUnit
            ]);

            DB::afterCommit(function () use ($freshStock) {
                StockLevelChanged::dispatch($freshStock, $freshStock->company_id, $freshStock->clinic_id);
            });

            return true;
        });
    }

    /**
     * Stok kullanımı yapar.
     * Race condition'a karşı pessimistic locking (lockForUpdate) kullanır.
     *
     * @throws StockNotFoundException    Stok bulunamazsa
     * @throws InsufficientStockException Yeterli stok yoksa
     */
    public function useStock(int $stockId, int $quantity, string $performedBy, int $userId = null, string $notes = null, bool $isFromReserved = false): bool
    {
        try {
            return DB::transaction(function () use ($stockId, $quantity, $performedBy, $userId, $notes, $isFromReserved) {
                // 🔒 Pessimistic lock: eşzamanlı kullanımlarda veri bütünlüğünü korur
                $stock = $this->stockRepository->findAndLock($stockId);
                if (!$stock) {
                    throw new StockNotFoundException($stockId);
                }

                // ⚠️ SKT Kontrolü
                if ($stock->expiry_date && $stock->expiry_date->isPast()) {
                    throw new \Exception("Bu stok partisinin son kullanma tarihi (" . $stock->expiry_date->format('d/m/Y') . ") geçmiştir. Kullanılamaz!");
                }

                // 🛡️ Rezerve kontrolü
                if ($isFromReserved && $stock->reserved_stock < $quantity) {
                    throw new InsufficientStockException($stock->reserved_stock, $quantity, 'Yeterli rezerve stok bulunmamaktadır.');
                }

                $isSubUnitUsage = $stock->has_sub_unit && $stock->sub_unit_multiplier > 0;
                $previousTotal  = $isSubUnitUsage ? $stock->total_base_units : $stock->current_stock;

                if ($isSubUnitUsage) {
                    $updateData = $this->handleSubUnitUsage($stock, $quantity, $isFromReserved);
                } else {
                    $updateData = $this->handleMainUnitUsage($stock, $quantity, $isFromReserved);
                }

                $this->stockRepository->update($stockId, $updateData);
                $freshStock = $stock->fresh();

                $this->createTransaction([
                    'stock_id'         => $stockId,
                    'clinic_id'        => $stock->clinic_id,
                    'type'             => 'usage',
                    'quantity'         => $quantity,
                    'previous_stock'   => $previousTotal,
                    'new_stock'        => $isSubUnitUsage ? $freshStock->total_base_units : $freshStock->current_stock,
                    'notes'            => $notes,
                    'performed_by'     => $performedBy,
                    'user_id'          => $userId,
                    'transaction_date' => now(),
                    'is_sub_unit'      => $isSubUnitUsage
                ]);

                DB::afterCommit(function () use ($freshStock) {
                    StockLevelChanged::dispatch($freshStock, $freshStock->company_id, $freshStock->clinic_id);
                });

                return true;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Usage Error: ' . $e->getMessage(), [
                'stock_id' => $stockId,
                'quantity' => $quantity,
                'user_id'  => auth()->id(),
                'trace'    => substr($e->getTraceAsString(), 0, 500)
            ]);
            throw $e;
        }
    }

    private function handleSubUnitUsage(Stock $stock, int $quantity, bool $isFromReserved): array
    {
        $newLevels = $this->calculatorService->calculateUsage(
            $stock->current_stock,
            $stock->current_sub_stock,
            $quantity,
            $stock->sub_unit_multiplier
        );

        if (!$newLevels) {
            throw new InsufficientStockException($stock->total_base_units, $quantity);
        }

        // 🛡️ KRITIK DÜZELTME: Rezerve stok sadece ANA BIRIM üzerinden takip ediliyor.
        // Alt birim kullanımı durumunda rezerve düşmek istiyorsak, 
        // ya tam birim düşmeliyiz ya da bu işleme izin vermemeliyiz.
        // Şimdilik alt birimden rezerve kullanımını engelliyoruz (unit mismatch önlemek için).
        if ($isFromReserved) {
             throw new \Exception('Alt birim kullanımı için rezerve stoktan düşüm yapılamaz. Lütfen ana birim üzerinden işlem yapın.');
        }

        $newReservedStock = $stock->reserved_stock;

        return array_merge($newLevels, [
            'reserved_stock'       => $newReservedStock,
            'available_stock'      => $newLevels['current_stock'] - $newReservedStock,
            'internal_usage_count' => $stock->internal_usage_count + $quantity
        ]);
    }

    private function handleMainUnitUsage(Stock $stock, int $quantity, bool $isFromReserved): array
    {
        if (!$isFromReserved && $stock->available_stock < $quantity) {
            throw new InsufficientStockException($stock->available_stock, $quantity);
        }

        if ($isFromReserved && $stock->current_stock < $quantity) {
            throw new InsufficientStockException($stock->current_stock, $quantity);
        }

        $newMainStock     = $stock->current_stock - $quantity;
        $newReservedStock = $isFromReserved ? ($stock->reserved_stock - $quantity) : $stock->reserved_stock;

        return [
            'current_stock'        => $newMainStock,
            'reserved_stock'       => $newReservedStock,
            'available_stock'      => $newMainStock - $newReservedStock,
            'internal_usage_count' => $stock->internal_usage_count + $quantity
        ];
    }

    public function createStock(array $data): Stock
    {
        return DB::transaction(function () use ($data) {
            $data['company_id'] = $data['company_id'] ?? auth()->user()?->company_id;
            $data['available_stock'] = ($data['current_stock'] ?? 0) - ($data['reserved_stock'] ?? 0);
            $data['current_sub_stock'] = $data['current_sub_stock'] ?? 0;
            
            $stock = $this->stockRepository->create($data);

            $this->createTransaction([
                'stock_id'         => $stock->id,
                'clinic_id'        => $stock->clinic_id,
                'type'             => 'purchase',
                'quantity'         => $stock->current_stock,
                'previous_stock'   => 0,
                'new_stock'        => $stock->total_base_units,
                'description'      => 'İlk stok girişi',
                'performed_by'     => auth()->user()?->name ?? 'Sistem',
                'transaction_date' => now(),
                'is_sub_unit'      => false
            ]);

            DB::afterCommit(function () use ($stock) {
                StockLevelChanged::dispatch($stock, $stock->company_id, $stock->clinic_id);
            });

            return $stock;
        });
    }

    public function deleteStock(int $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $stock = $this->stockRepository->find($id);
                if (!$stock) return false;

                $stock->alerts()->delete();
                return $this->stockRepository->delete($id);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Deletion Error: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    public function forceDeleteStock(int $id): bool
    {
        try {
            return $this->stockRepository->forceDelete($id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Force Deletion Error: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    public function getStockStats(int $companyId, int $clinicId = null): array
    {
        $cacheKey  = "stock_stats_{$companyId}_" . ($clinicId ?? 'all');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(5), function () use ($clinicId, $companyId) {
            try {
                $baseQuery = $this->stockRepository->getBaseQuery();
                if ($clinicId) $baseQuery->where('clinic_id', $clinicId);

                $nearExpiryLimit = now()->addDays(30)->toDateTimeString();
                $now             = now()->toDateTimeString();

                $totalUnitsRaw = Stock::totalBaseUnitsRaw();
                $stats = $baseQuery->join('products', 'stocks.product_id', '=', 'products.id')
                    ->selectRaw("
                        COUNT(*) as total_items,
                        SUM(CASE WHEN stocks.is_active = 1 AND {$totalUnitsRaw} <= COALESCE(products.yellow_alert_level, products.min_stock_level) THEN 1 ELSE 0 END) as low_stock_items,
                        SUM(CASE WHEN stocks.is_active = 1 AND {$totalUnitsRaw} <= COALESCE(products.red_alert_level, products.critical_stock_level) THEN 1 ELSE 0 END) as critical_stock_items,
                        SUM(CASE WHEN stocks.is_active = 1 AND stocks.track_expiry = 1 AND stocks.expiry_date <= ? AND stocks.expiry_date > ? THEN 1 ELSE 0 END) as expiring_items,
                        SUM(stocks.purchase_price * stocks.current_stock) as total_value
                    ", [$nearExpiryLimit, $now])->first();

                return [
                    'total_items'          => (int) ($stats->total_items          ?? 0),
                    'low_stock_items'      => (int) ($stats->low_stock_items      ?? 0),
                    'critical_stock_items' => (int) ($stats->critical_stock_items ?? 0),
                    'expiring_items'       => (int) ($stats->expiring_items       ?? 0),
                    'total_value'          => round((float) ($stats->total_value  ?? 0), 2)
                ];
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Stock Stats Error: ' . $e->getMessage(), ['company_id' => $companyId]);
                return ['total_items' => 0, 'low_stock_items' => 0, 'critical_stock_items' => 0, 'expiring_items' => 0, 'total_value' => 0];
            }
        });
    }

    public function getLowStockItems(int $clinicId = null): Collection
    {
        return $this->stockRepository->getLowStockItems($clinicId);
    }

    public function getCriticalStockItems(int $clinicId = null): Collection
    {
        return $this->stockRepository->getCriticalStockItems($clinicId);
    }

    public function getExpiringItems(int $days = 30, int $clinicId = null): Collection
    {
        return $this->stockRepository->getExpiringItems($days, $clinicId);
    }

    public function getStockTransactions(int $stockId): Collection
    {
        return $this->stockRepository->getTransactions($stockId);
    }

    protected function createTransaction(array $data): void
    {
        $data['transaction_number'] = $this->generateTransactionNumber();
        $this->transactionService->createTransaction($data);
    }

    /**
     * Benzersiz işlem numarası üretir.
     * UUID substring yerine Str::random(12) kullanarak çakışma riskini azaltır.
     */
    protected function generateTransactionNumber(): string
    {
        return 'TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(12));
    }

    public function reverseTransaction(int $transactionId): bool
    {
        try {
            return DB::transaction(function () use ($transactionId) {
                $transaction = \App\Models\StockTransaction::findOrFail($transactionId);
                $stock = $transaction->stock()->lockForUpdate()->first();
                
                if (!$stock) return false;

                $positiveTypes = ['purchase', 'adjustment_increase', 'transfer_in', 'return_in'];
                $negativeTypes = ['usage', 'adjustment_decrease', 'transfer_out', 'damaged', 'expired', 'return_out'];
                
                $isPositiveEffect = in_array($transaction->type, $positiveTypes);
                $isNegativeEffect = in_array($transaction->type, $negativeTypes);

                $quantity = $transaction->quantity;

                if ($transaction->is_sub_unit && $stock->has_sub_unit && $stock->sub_unit_multiplier > 0) {
                    // if it was negative (usage), we ADD it back. if positive (purchase), we SUBTRACT.
                    $delta = $isNegativeEffect ? $quantity : -$quantity;
                    
                    $newLevels = $this->calculatorService->calculateAdjustment(
                        $stock->current_stock,
                        $stock->current_sub_stock,
                        $delta,
                        $stock->sub_unit_multiplier
                    );
                    
                    $stock->current_stock = $newLevels['current_stock'];
                    $stock->current_sub_stock = $newLevels['current_sub_stock'];
                } else {
                    if ($isNegativeEffect) {
                        $stock->current_stock += $quantity;
                    } elseif ($isPositiveEffect) {
                        $stock->current_stock -= $quantity;
                    }
                }

                $stock->available_stock = $stock->current_stock - $stock->reserved_stock;
                $stock->save();
                
                \App\Events\Stock\StockLevelChanged::dispatch($stock, $stock->company_id, $stock->clinic_id);

                return $transaction->delete();
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Transaction Reversal Error: ' . $e->getMessage(), ['transaction_id' => $transactionId]);
            throw $e;
        }
    }
}
