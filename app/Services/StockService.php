<?php

namespace App\Services;

use App\Events\Stock\StockLevelChanged;
use App\Exceptions\Stock\InsufficientStockException;
use App\Exceptions\Stock\StockNotFoundException;
use App\Models\Stock;
use App\Repositories\Interfaces\StockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockService
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository,
        protected StockCalculatorService $calculatorService,
        protected StockTransactionService $transactionService
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
            $stock = $this->stockRepository->findAndLock($id);
            if (! $stock) {
                return null;
            }

            $targetCurrentStock = array_key_exists('current_stock', $data) ? (int) $data['current_stock'] : null;
            unset($data['current_stock'], $data['available_stock']);

            if (array_key_exists('reserved_stock', $data)) {
                $data['reserved_stock'] = max(0, (int) $data['reserved_stock']);
                $data['available_stock'] = $stock->current_stock - $data['reserved_stock'];
            }

            $updatedStock = $this->stockRepository->update($id, $data);

            if ($targetCurrentStock !== null && $targetCurrentStock !== (int) $stock->current_stock) {
                $delta = $targetCurrentStock - (int) $stock->current_stock;
                $this->createTransaction([
                    'stock_id' => $stock->id,
                    'clinic_id' => $stock->clinic_id,
                    'type' => $delta > 0 ? 'adjustment_increase' : 'adjustment_decrease',
                    'quantity' => abs($delta),
                    'previous_stock' => $stock->current_stock,
                    'new_stock' => $targetCurrentStock,
                    'description' => 'Stok miktarı güncellemesi',
                    'performed_by' => auth()->user()->name ?? 'Sistem',
                    'user_id' => auth()->id(),
                    'transaction_date' => now(),
                    'is_sub_unit' => false,
                ]);
            }

            if ($updatedStock) {
                DB::afterCommit(function () use ($updatedStock) {
                    StockLevelChanged::dispatch($updatedStock->fresh(), $updatedStock->clinic_id);
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
            if (! $stock) {
                throw new StockNotFoundException($stockId);
            }

            $previousTotal = $stock->total_base_units;

            // Business Logic: Calculate delta if sync
            if ($type === 'sync') {
                $current = $isSubUnit ? $stock->current_sub_stock : $stock->current_stock;
                $delta = $targetQuantity - $current;
                if ($delta === 0) {
                    return true;
                }
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
                    ! $newLevels ||
                    ($newLevels['current_stock'] ?? 0) < 0 ||
                    ($newLevels['current_sub_stock'] ?? 0) < 0
                ) {
                    throw new InsufficientStockException($stock->total_base_units, abs($delta));
                }
                // Observer handles the balance update via Transaction creation
            } else {
                $newStock = $stock->current_stock + $delta;
                if ($newStock < 0) {
                    throw new InsufficientStockException($stock->current_stock, abs($delta));
                }
                // Observer handles the balance update via Transaction creation
            }

            $this->createTransaction([
                'stock_id' => $stockId,
                'clinic_id' => $stock->clinic_id,
                'type' => $delta > 0 ? 'adjustment_increase' : 'adjustment_decrease',
                'quantity' => abs($delta),
                'previous_stock' => $previousTotal,
                'new_stock' => $previousTotal + $delta,
                'description' => ($isSubUnit ? 'Alt Birim Düzeltme: ' : 'Ana Birim Düzeltme: ').$reason,
                'performed_by' => $performedBy,
                'transaction_date' => now(),
                'is_sub_unit' => $isSubUnit,
            ]);

            return true;
        });
    }

    /**
     * Stok kullanımı yapar.
     * Race condition'a karşı pessimistic locking (lockForUpdate) kullanır.
     *
     * @throws StockNotFoundException Stok bulunamazsa
     * @throws InsufficientStockException Yeterli stok yoksa
     */
    public function useStock(
        int $stockId,
        int $quantity,
        string $performedBy,
        ?int $userId = null,
        ?string $notes = null,
        bool $isFromReserved = false,
        bool $isSubUnit = false,
        ?bool $showZeroStockInCritical = null
    ): bool {
        try {
            return DB::transaction(function () use ($stockId, $quantity, $performedBy, $userId, $notes, $isFromReserved, $isSubUnit, $showZeroStockInCritical) {
                // 🔒 Pessimistic lock: eşzamanlı kullanımlarda veri bütünlüğünü korur
                $stock = $this->stockRepository->findAndLock($stockId);
                if (! $stock) {
                    throw new StockNotFoundException($stockId);
                }

                // ⚠️ SKT Kontrolü
                if ($stock->expiry_date && $stock->expiry_date->isPast()) {
                    throw new \Exception(__('stocks.errors.expired_batch', ['date' => $stock->expiry_date->format('d/m/Y')]));
                }

                // 🛡️ Rezerve kontrolü
                if ($isFromReserved && $stock->reserved_stock < $quantity) {
                    throw new InsufficientStockException($stock->reserved_stock, $quantity, __('stocks.errors.insufficient_reserved'));
                }

                if ($isSubUnit && (! $stock->has_sub_unit || (int) ($stock->sub_unit_multiplier ?? 0) <= 0)) {
                    throw new \Exception(__('stocks.errors.sub_unit_not_active'));
                }

                $isSubUnitUsage = $isSubUnit && $stock->has_sub_unit && $stock->sub_unit_multiplier > 0;
                $previousTotal = $isSubUnitUsage ? $stock->total_base_units : $stock->current_stock;

                if ($isSubUnitUsage) {
                    if ($isFromReserved) {
                        throw new \Exception('Alt birim kullanımı için rezerve stoktan düşüm yapılamaz. Lütfen ana birim üzerinden işlem yapın.');
                    }

                    if ($stock->total_base_units < $quantity) {
                        throw new InsufficientStockException($stock->total_base_units, $quantity);
                    }
                } elseif ($isFromReserved && $stock->current_stock < $quantity) {
                    throw new InsufficientStockException($stock->current_stock, $quantity);
                } elseif (! $isFromReserved && $stock->available_stock < $quantity) {
                    throw new InsufficientStockException($stock->available_stock, $quantity);
                }

                // 🛡️ Rezerve stok manuel yonetilir cunku TransactionObserver sadece current_stock'u etkiler.
                // Rezerve stoktan dusum yapiliyorsa rezerve miktarini azalt.
                if ($isFromReserved) {
                    $this->stockRepository->update($stockId, [
                        'reserved_stock' => $stock->reserved_stock - $quantity,
                    ]);
                }

                $this->createTransaction([
                    'stock_id' => $stockId,
                    'clinic_id' => $stock->clinic_id,
                    'type' => 'usage',
                    'quantity' => $quantity,
                    'previous_stock' => $previousTotal,
                    'new_stock' => $previousTotal - $quantity,
                    'notes' => $notes,
                    'performed_by' => $performedBy,
                    'user_id' => $userId,
                    'transaction_date' => now(),
                    'is_sub_unit' => $isSubUnitUsage,
                ]);

                if ($showZeroStockInCritical !== null) {
                    $updatedStock = Stock::with('product.batches')->find($stockId);
                    $product = $updatedStock?->product;

                    if ($product && (int) $product->total_stock === 0) {
                        $product->update([
                            'show_zero_stock_in_critical' => $showZeroStockInCritical,
                        ]);
                    }
                }

                return true;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Usage Error: '.$e->getMessage(), [
                'stock_id' => $stockId,
                'quantity' => $quantity,
                'user_id' => auth()->id(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
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

        if (! $newLevels) {
            throw new InsufficientStockException($stock->total_base_units, $quantity);
        }

        // 🛡️ DIVISION BY ZERO PROTECTION
        $multiplier = max(1, (int) ($stock->sub_unit_multiplier ?? 1));

        // 🛡️ KRITIK DÜZELTME: Rezerve stok sadece ANA BIRIM üzerinden takip ediliyor.
        // Alt birim kullanımı durumunda rezerve düşmek istiyorsak,
        // ya tam birim düşmeliyiz ya da bu işleme izin vermemeliyiz.
        // Şimdilik alt birimden rezerve kullanımını engelliyoruz (unit mismatch önlemek için).
        if ($isFromReserved) {
            throw new \Exception('Alt birim kullanımı için rezerve stoktan düşüm yapılamaz. Lütfen ana birim üzerinden işlem yapın.');
        }

        $newReservedStock = $stock->reserved_stock;

        return array_merge($newLevels, [
            'reserved_stock' => $newReservedStock,
            'available_stock' => $newLevels['current_stock'] - $newReservedStock,
            'internal_usage_count' => $stock->internal_usage_count + $quantity,
        ]);
    }

    private function handleMainUnitUsage(Stock $stock, int $quantity, bool $isFromReserved): array
    {
        if (! $isFromReserved && $stock->available_stock < $quantity) {
            throw new InsufficientStockException($stock->available_stock, $quantity);
        }

        if ($isFromReserved && $stock->current_stock < $quantity) {
            throw new InsufficientStockException($stock->current_stock, $quantity);
        }

        $newMainStock = $stock->current_stock - $quantity;
        $newReservedStock = $isFromReserved ? ($stock->reserved_stock - $quantity) : $stock->reserved_stock;

        return [
            'current_stock' => $newMainStock,
            'reserved_stock' => $newReservedStock,
            'available_stock' => $newMainStock - $newReservedStock,
            'internal_usage_count' => $stock->internal_usage_count + $quantity,
        ];
    }

    public function createStock(array $data): Stock
    {
        return DB::transaction(function () use ($data) {
            $initialQuantity = $data['current_stock'] ?? 0;

            // Başlangıç stoğunu 0 olarak kaydediyoruz, çünkü StockTransaction oluşturulduğunda
            // StockTransactionObserver otomatik olarak stoğu olması gereken değere yükseltecek.
            // Böylece stok iki kere (çift) sayılmamış olacak.
            $data['current_stock'] = 0;
            $data['available_stock'] = 0;
            $data['current_sub_stock'] = $data['current_sub_stock'] ?? 0;

            $stock = $this->stockRepository->create($data);

            if ($initialQuantity > 0) {
                $this->createTransaction([
                    'stock_id' => $stock->id,
                    'clinic_id' => $stock->clinic_id,
                    'type' => 'purchase',
                    'quantity' => $initialQuantity,
                    'previous_stock' => 0,
                    'new_stock' => $initialQuantity,
                    'description' => 'İlk stok girişi',
                    'performed_by' => auth()->user()->name ?? 'Sistem',
                    'transaction_date' => now(),
                    'is_sub_unit' => false,
                ]);
            } else {
                DB::afterCommit(function () use ($stock) {
                    StockLevelChanged::dispatch($stock, $stock->clinic_id);
                });
            }

            return $stock->fresh();
        });
    }

    public function deleteStock(int $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $stock = $this->stockRepository->find($id);
                if (! $stock) {
                    return false;
                }

                $stock->alerts()->delete();

                return $this->stockRepository->delete($id);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Deletion Error: '.$e->getMessage(), ['id' => $id]);

            return false;
        }
    }

    public function forceDeleteStock(int $id): bool
    {
        try {
            return $this->stockRepository->forceDelete($id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Force Deletion Error: '.$e->getMessage(), ['id' => $id]);

            return false;
        }
    }

    public function getStockStats(?int $clinicId = null): array
    {
        return $this->calculateStockStats($clinicId);
    }

    protected function calculateStockStats(?int $clinicId = null): array
    {
        try {
            $now = now()->toDateTimeString();
            $totalUnitsRaw = Stock::totalBaseUnitsRaw();
            $driver = DB::getDriverName();

            // PostgreSQL compatibility
            $isPostgres = $driver === 'pgsql';
            $isSqlite = $driver === 'sqlite';

            $redDaysSql = $isPostgres
                ? 'CAST(? AS DATE) + CAST(COALESCE(stocks.expiry_red_days, 15) AS INTEGER)'
                : ($isSqlite ? "date(?, '+' || COALESCE(stocks.expiry_red_days, 15) || ' days')" : 'DATE_ADD(?, INTERVAL COALESCE(stocks.expiry_red_days, 15) DAY)');

            $yellowDaysSql = $isPostgres
                ? 'CAST(? AS DATE) + CAST(COALESCE(stocks.expiry_yellow_days, 30) AS INTEGER)'
                : ($isSqlite ? "date(?, '+' || COALESCE(stocks.expiry_yellow_days, 30) || ' days')" : 'DATE_ADD(?, INTERVAL COALESCE(stocks.expiry_yellow_days, 30) DAY)');

            // 1. Ürün Bazlı Stok Özet Alt Sorgusu
            $stockSummaryQuery = DB::table('stocks')
                ->select('product_id')
                ->selectRaw("SUM({$totalUnitsRaw}) as total_stock")
                ->selectRaw('SUM(purchase_price * current_stock) as total_value')
                ->whereNull('deleted_at')
                ->where('is_active', true);

            if ($clinicId) {
                $stockSummaryQuery->where('clinic_id', $clinicId);
            }

            $stockSummaryQuery->groupBy('product_id');

            // 2. Ana Sorgu (Products üzerinden Left Join)
            $statsQuery = DB::table('products')
                ->leftJoinSub($stockSummaryQuery, 'stock_summary', 'products.id', '=', 'stock_summary.product_id')
                ->whereNull('products.deleted_at')
                ->where('products.is_active', true);

            if ($clinicId) {
                $statsQuery->where(function ($q) use ($clinicId) {
                    $q->where('products.clinic_id', $clinicId)
                        ->orWhereNotNull('stock_summary.product_id');
                });
            }

            $stats = $statsQuery->selectRaw('
                COUNT(products.id) as total_items,
                
                -- Kritik Stok (Ürün Toplam Stok Bazlı)
                COUNT(CASE WHEN COALESCE(stock_summary.total_stock, 0) <= COALESCE(products.red_alert_level, products.critical_stock_level, 5)
                    AND NOT (COALESCE(stock_summary.total_stock, 0) = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0)
                    THEN 1 END) as critical_stock_items,

                -- Düşük Stok (Ürün Toplam Stok Bazlı)
                COUNT(CASE WHEN COALESCE(stock_summary.total_stock, 0) <= COALESCE(products.yellow_alert_level, products.min_stock_level, 10)
                    AND (COALESCE(stock_summary.total_stock, 0) > COALESCE(products.red_alert_level, products.critical_stock_level, 5)
                        OR (COALESCE(stock_summary.total_stock, 0) = 0 AND COALESCE(products.show_zero_stock_in_critical, 1) = 0))
                    THEN 1 END) as low_stock_items,
                
                SUM(COALESCE(stock_summary.total_value, 0)) as total_value
            ')->first();

            // 3. Miyat (SKT) Uyarıları (Batch Bazlı - Her zaman batch bazlı olmalı)
            $expiryQuery = DB::table('stocks')
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->where('track_expiry', true);

            if ($clinicId) {
                $expiryQuery->where('clinic_id', $clinicId);
            }

            $expiryStats = $expiryQuery->selectRaw("
                COUNT(DISTINCT CASE WHEN expiry_date <= {$redDaysSql} THEN id END) as critical_expiring_items,
                COUNT(DISTINCT CASE WHEN expiry_date <= {$yellowDaysSql} AND expiry_date > {$redDaysSql} THEN id END) as low_expiring_items
            ", [$now, $now, $now])->first();

            return [
                'total_items' => (int) ($stats->total_items ?? 0),
                'total_batches' => (int) DB::table('stocks')->whereNull('deleted_at')->where('is_active', true)->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
                'low_stock_items' => (int) ($stats->low_stock_items ?? 0),
                'critical_stock_items' => (int) ($stats->critical_stock_items ?? 0),
                'low_expiring_items' => (int) ($expiryStats->low_expiring_items ?? 0),
                'critical_expiring_items' => (int) ($expiryStats->critical_expiring_items ?? 0),
                'total_value' => round((float) ($stats->total_value ?? 0), 2),
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stock Stats Error: '.$e->getMessage());

            return [
                'total_items' => 0,
                'total_batches' => 0,
                'low_stock_items' => 0,
                'critical_stock_items' => 0,
                'low_expiring_items' => 0,
                'critical_expiring_items' => 0,
                'total_value' => 0,
            ];
        }
    }

    public function getLowStockItems(?int $clinicId = null): Collection
    {
        return $this->stockRepository->getLowStockItems($clinicId);
    }

    public function getCriticalStockItems(?int $clinicId = null): Collection
    {
        return $this->stockRepository->getCriticalStockItems($clinicId);
    }

    public function getExpiringItems(int $days = 30, ?int $clinicId = null): Collection
    {
        return $this->stockRepository->getExpiringItems($days, $clinicId);
    }

    public function getStockTransactions(int $stockId, array $filters = []): mixed
    {
        $query = \App\Models\StockTransaction::where('stock_id', $stockId)
            ->with(['user', 'clinic', 'stock.product'])
            ->orderByDesc('transaction_date');

        // Type filter
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Date range filter
        if (! empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 50;
        if ($perPage && $perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    protected function createTransaction(array $data): void
    {
        $data['transaction_number'] = $this->generateTransactionNumber();
        $transaction = $this->transactionService->createTransaction($data);
        $this->applyTransactionToStock($transaction);
    }

    public function applyTransactionToStock(\App\Models\StockTransaction $transaction): void
    {
        $stock = $transaction->stock;
        if (! $stock) {
            return;
        }

        $isPositive = match ($transaction->type) {
            'entry', 'adjustment_plus', 'adjustment_increase', 'purchase', 'transfer_in', 'returned', 'return_in' => true,
            'usage', 'loss', 'adjustment_minus', 'adjustment_decrease', 'transfer_out', 'expired', 'damaged', 'return_out' => false,
            default => null,
        };

        if ($isPositive === null) {
            return;
        }

        $quantity = (int) $transaction->quantity;

        if ($transaction->is_sub_unit && $stock->has_sub_unit && $stock->sub_unit_multiplier > 0) {
            $this->handleSubUnitCalculation($stock, $quantity, $isPositive);
        } else {
            if ($isPositive) {
                $stock->increment('current_stock', $quantity);
            } else {
                $stock->decrement('current_stock', $quantity);
            }

            $stock->updateQuietly([
                'available_stock' => $stock->current_stock - $stock->reserved_stock,
            ]);
        }

        \App\Events\Stock\StockLevelChanged::dispatch($stock->fresh(), $stock->clinic_id);
    }

    private function handleSubUnitCalculation(Stock $stock, int $quantity, bool $isPositive): void
    {
        $multiplier = (int) $stock->sub_unit_multiplier;
        $stock->refresh();

        if ($isPositive) {
            $totalSubUnits = $stock->current_sub_stock + $quantity;
            $extraBaseUnits = (int) floor($totalSubUnits / $multiplier);
            $newSubStock = $totalSubUnits % $multiplier;

            if ($extraBaseUnits > 0) {
                $stock->increment('current_stock', $extraBaseUnits);
            }
            $stock->current_sub_stock = $newSubStock;
        } else {
            $currentTotalSub = ($stock->current_stock * $multiplier) + $stock->current_sub_stock;
            $newTotalSub = max(0, $currentTotalSub - $quantity);

            $stock->current_stock = (int) floor($newTotalSub / $multiplier);
            $stock->current_sub_stock = $newTotalSub % $multiplier;
        }

        $stock->available_stock = $stock->current_stock - $stock->reserved_stock;
        $stock->saveQuietly();
    }

    /**
     * Benzersiz işlem numarası üretir.
     * UUID substring yerine Str::random(12) kullanarak çakışma riskini azaltır.
     */
    protected function generateTransactionNumber(): string
    {
        return 'TXN-'.now()->format('Ymd').'-'.strtoupper(Str::random(12));
    }

    public function reverseTransaction(int $transactionId): bool
    {
        try {
            return DB::transaction(function () use ($transactionId) {
                // 🛡️ DEADLOCK PREVENTION: Always lock the parent resource (Stock) BEFORE the child (Transaction)
                $tempTxn = \App\Models\StockTransaction::findOrFail($transactionId);
                $stock = $tempTxn->stock()->lockForUpdate()->first();

                /** @var \App\Models\Stock $stock */
                $transaction = \App\Models\StockTransaction::whereKey($transactionId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($transaction->reversed_at !== null) {
                    throw new \Exception(__('stocks.errors.already_reversed'));
                }

                if ($transaction->reversal_transaction_id !== null) {
                    throw new \Exception(__('stocks.errors.cannot_reverse_reversal'));
                }

                if (! $stock) {
                    return false;
                }

                $reversalType = $this->oppositeTransactionType($transaction->type);
                if ($reversalType === null) {
                    throw new \Exception('Bu hareket tipi geri alınamaz: '.$transaction->type);
                }

                /** @var \App\Models\Stock $stock */
                $previousStock = $transaction->is_sub_unit && $stock->has_sub_unit
                    ? $stock->total_base_units
                    : $stock->current_stock;
                $newStock = $this->calculateReversalNewStock($previousStock, (int) $transaction->quantity, $reversalType);

                $reversal = $this->transactionService->createTransaction([
                    'transaction_number' => $this->generateTransactionNumber(),
                    'stock_id' => $transaction->stock_id,
                    'clinic_id' => $transaction->clinic_id,
                    'type' => $reversalType,
                    'quantity' => $transaction->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'unit_price' => $transaction->unit_price,
                    'total_price' => $transaction->total_price,
                    'stock_request_id' => $transaction->stock_request_id,
                    'reference_number' => $transaction->reference_number,
                    'batch_number' => $transaction->batch_number,
                    'description' => 'Reversal of '.$transaction->transaction_number,
                    'notes' => $transaction->notes,
                    'performed_by' => auth()->user()->name ?? 'Sistem',
                    'user_id' => auth()->id(),
                    'transaction_date' => now(),
                    'is_sub_unit' => $transaction->is_sub_unit,
                ]);

                $transaction->update([
                    'reversed_at' => now(),
                    'reversed_by' => auth()->id(),
                    'reversal_transaction_id' => $reversal->id,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Transaction Reversal Error: '.$e->getMessage(), ['transaction_id' => $transactionId]);
            throw $e;
        }
    }

    private function oppositeTransactionType(string $type): ?string
    {
        return match ($type) {
            'purchase', 'entry', 'adjustment_plus', 'adjustment_increase', 'transfer_in', 'returned', 'return_in' => 'adjustment_decrease',
            'usage', 'loss', 'adjustment_minus', 'adjustment_decrease', 'transfer_out', 'expired', 'damaged', 'return_out' => 'adjustment_increase',
            default => null,
        };
    }

    private function calculateReversalNewStock(int $previousStock, int $quantity, string $reversalType): int
    {
        return match ($reversalType) {
            'adjustment_increase' => $previousStock + $quantity,
            'adjustment_decrease' => $previousStock - $quantity,
            default => $previousStock,
        };
    }
}
