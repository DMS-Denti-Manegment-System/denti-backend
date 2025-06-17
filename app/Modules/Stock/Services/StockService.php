<?php
// app/Modules/Stock/Services/StockService.php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\StockRepositoryInterface;
use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Jobs\CheckStockLevelsJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StockService
{
    protected $stockRepository;

    public function __construct(StockRepositoryInterface $stockRepository)
    {
        $this->stockRepository = $stockRepository;
    }

    public function getAllStocks(array $filters = []): Collection
    {
        return $this->stockRepository->getAllWithFilters($filters);
    }

    public function createStock(array $data): Stock
    {
        return DB::transaction(function () use ($data) {
            // Stok kodu otomatik oluştur
            if (!isset($data['code'])) {
                $data['code'] = $this->generateStockCode($data['clinic_id']);
            }

            // Kullanılabilir stok hesapla
            $data['available_stock'] = $data['current_stock'] - ($data['reserved_stock'] ?? 0);

            $stock = $this->stockRepository->create($data);

            // Stok seviyesi kontrolü
            $this->checkStockLevels($stock);

            return $stock;
        });
    }

    public function updateStock(int $id, array $data): ?Stock
    {
        return DB::transaction(function () use ($id, $data) {
            $stock = $this->stockRepository->find($id);
            if (!$stock) return null;

            // Kullanılabilir stok güncelle
            if (isset($data['current_stock']) || isset($data['reserved_stock'])) {
                $currentStock = $data['current_stock'] ?? $stock->current_stock;
                $reservedStock = $data['reserved_stock'] ?? $stock->reserved_stock;
                $data['available_stock'] = $currentStock - $reservedStock;
            }

            $updatedStock = $this->stockRepository->update($id, $data);

            // Stok seviyesi kontrolü
            if ($updatedStock) {
                $this->checkStockLevels($updatedStock);
            }

            return $updatedStock;
        });
    }

    public function adjustStock(int $stockId, int $quantity, string $reason, string $performedBy): bool
    {
        return DB::transaction(function () use ($stockId, $quantity, $reason, $performedBy) {
            $stock = $this->stockRepository->find($stockId);
            if (!$stock) return false;

            $previousStock = $stock->current_stock;
            $newStock = $previousStock + $quantity;

            // Stok güncelle
            $this->stockRepository->update($stockId, [
                'current_stock' => $newStock,
                'available_stock' => $newStock - $stock->reserved_stock
            ]);

            // İşlem kaydı oluştur
            $this->createTransaction([
                'stock_id' => $stockId,
                'clinic_id' => $stock->clinic_id,
                'type' => 'adjustment',
                'quantity' => abs($quantity),
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'description' => $reason,
                'performed_by' => $performedBy,
                'transaction_date' => now()
            ]);

            // Stok seviyesi kontrolü
            $this->checkStockLevels($stock->fresh());

            return true;
        });
    }

    public function useStock(int $stockId, int $quantity, string $performedBy, string $notes = null): bool
    {
        return DB::transaction(function () use ($stockId, $quantity, $performedBy, $notes) {
            $stock = $this->stockRepository->find($stockId);
            if (!$stock || $stock->available_stock < $quantity) {
                return false;
            }

            $previousStock = $stock->current_stock;
            $newStock = $previousStock - $quantity;

            // Stok güncelle
            $this->stockRepository->update($stockId, [
                'current_stock' => $newStock,
                'available_stock' => $newStock - $stock->reserved_stock,
                'internal_usage_count' => $stock->internal_usage_count + $quantity
            ]);

            // İşlem kaydı oluştur
            $this->createTransaction([
                'stock_id' => $stockId,
                'clinic_id' => $stock->clinic_id,
                'type' => 'usage',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'notes' => $notes,
                'performed_by' => $performedBy,
                'transaction_date' => now()
            ]);

            // Stok seviyesi kontrolü
            $this->checkStockLevels($stock->fresh());

            return true;
        });
    }

    protected function generateStockCode(int $clinicId): string
    {
        $clinic = app(ClinicService::class)->getClinicById($clinicId);
        $prefix = $clinic ? $clinic->code : 'STK';
        $sequence = $this->stockRepository->getNextSequenceNumber($clinicId);

        return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    protected function checkStockLevels(Stock $stock): void
    {
        // Job olarak çalıştır (async)
        CheckStockLevelsJob::dispatch($stock);
    }

    protected function createTransaction(array $data): void
    {
        // Transaction numarası oluştur
        $data['transaction_number'] = $this->generateTransactionNumber();

        app(StockTransactionService::class)->createTransaction($data);
    }

    protected function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = DB::table('stock_transactions')
                     ->whereDate('created_at', now())
                     ->count() + 1;

        return 'TXN-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
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

    public function getExpiredItems(int $clinicId = null): Collection
    {
        return $this->stockRepository->getExpiredItems($clinicId);
    }
}