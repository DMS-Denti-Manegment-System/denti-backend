<?php
// app/Modules/Stock/Services/StockRequestService.php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\StockRequestRepositoryInterface;
use App\Modules\Stock\Repositories\Interfaces\StockRepositoryInterface;
use App\Modules\Stock\Services\StockTransactionService;
use App\Modules\Stock\Models\StockRequest;
use App\Modules\Stock\Jobs\SendStockRequestNotificationJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StockRequestService
{
    protected $stockRequestRepository;
    protected $stockService;

    public function __construct(
        StockRequestRepositoryInterface $stockRequestRepository,
        StockService $stockService
    ) {
        $this->stockRequestRepository = $stockRequestRepository;
        $this->stockService = $stockService;
    }

    // ✅ EKSİK METOD EKLENDİ
    public function getAllRequests(): Collection
    {
        return $this->stockRequestRepository->all();
    }

    // ✅ EKSİK METOD EKLENDİ
    public function getRequestById(int $id): ?StockRequest
    {
        return $this->stockRequestRepository->find($id);
    }

    public function createRequest(array $data): StockRequest
    {
        return DB::transaction(function () use ($data) {
            // Talep numarası oluştur
            $data['request_number'] = $this->generateRequestNumber();
            $data['requested_at'] = now();
            $data['status'] = 'pending';

            $request = $this->stockRequestRepository->create($data);

            // Bildirim gönder
            SendStockRequestNotificationJob::dispatch($request);

            return $request;
        });
    }

    // ✅ HATA DÜZELTİLDİ: Missing interface import
    public function approveRequest(int $requestId, int $approvedQuantity, string $approvedBy, string $notes = null): bool
    {
        return DB::transaction(function () use ($requestId, $approvedQuantity, $approvedBy, $notes) {
            $request = $this->stockRequestRepository->find($requestId);
            if (!$request || $request->status !== 'pending') {
                throw new \Exception('Geçersiz talep veya talep zaten işlenmiş');
            }

            // Stok kontrol
            if ($request->stock->available_stock < $approvedQuantity) {
                throw new \Exception('Yetersiz stok miktarı');
            }

            // Talebi onayla
            $this->stockRequestRepository->update($requestId, [
                'status' => 'approved',
                'approved_quantity' => $approvedQuantity,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'admin_notes' => $notes
            ]);

            // Stoku rezerve et
            $this->reserveStock($request->stock_id, $approvedQuantity);

            return true;
        });
    }

    // ✅ HATA DÜZELTİLDİ: Missing interface import
    public function completeRequest(int $requestId, string $performedBy): bool
    {
        return DB::transaction(function () use ($requestId, $performedBy) {
            $request = $this->stockRequestRepository->find($requestId);
            if (!$request || $request->status !== 'approved') {
                throw new \Exception('Talep bulunamadı veya onaylanmamış');
            }

            // Transfer işlemi gerçekleştir
            $this->transferStock($request, $performedBy);

            // Talebi tamamla
            $this->stockRequestRepository->update($requestId, [
                'status' => 'completed',
                'completed_at' => now()
            ]);

            return true;
        });
    }

    public function rejectRequest(int $requestId, string $rejectionReason, string $rejectedBy): bool
    {
        $request = $this->stockRequestRepository->find($requestId);
        if (!$request || $request->status !== 'pending') {
            throw new \Exception('Talep bulunamadı veya zaten işlenmiş');
        }

        return (bool) $this->stockRequestRepository->update($requestId, [
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
            'approved_by' => $rejectedBy,
            'approved_at' => now()
        ]);
    }

    protected function generateRequestNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = DB::table('stock_requests')
                     ->whereDate('created_at', now())
                     ->count() + 1;

        return 'REQ-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    protected function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = DB::table('stock_transactions')
                     ->whereDate('created_at', now())
                     ->count() + 1;

        return 'TXN-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // ✅ HATA DÜZELTİLDİ: Proper interface resolution
    protected function reserveStock(int $stockId, int $quantity): void
    {
        $stockRepository = app(StockRepositoryInterface::class);
        $stock = $stockRepository->find($stockId);

        if ($stock) {
            $stockRepository->update($stockId, [
                'reserved_stock' => $stock->reserved_stock + $quantity,
                'available_stock' => $stock->available_stock - $quantity
            ]);
        }
    }

    // ✅ HATA DÜZELTİLDİ: Proper service resolution
    protected function transferStock(StockRequest $request, string $performedBy): void
    {
        $sourceStock = $request->stock;
        $quantity = $request->approved_quantity;
        $stockRepository = app(StockRepositoryInterface::class);
        $transactionService = app(StockTransactionService::class);

        // Kaynak klinikten çıkış
        $transactionService->createTransaction([
            'transaction_number' => $this->generateTransactionNumber(),
            'stock_id' => $sourceStock->id,
            'clinic_id' => $request->requested_from_clinic_id,
            'type' => 'transfer_out',
            'quantity' => $quantity,
            'previous_stock' => $sourceStock->current_stock,
            'new_stock' => $sourceStock->current_stock - $quantity,
            'stock_request_id' => $request->id,
            'description' => "Transfer to {$request->requesterClinic->name}",
            'performed_by' => $performedBy,
            'transaction_date' => now()
        ]);

        // Kaynak stoku güncelle
        $stockRepository->update($sourceStock->id, [
            'current_stock' => $sourceStock->current_stock - $quantity,
            'reserved_stock' => $sourceStock->reserved_stock - $quantity,
            'available_stock' => $sourceStock->current_stock - $quantity - ($sourceStock->reserved_stock - $quantity)
        ]);

        // Hedef klinikteki stoku bul veya oluştur
        $targetStock = $this->findOrCreateTargetStock($sourceStock, $request->requester_clinic_id);

        // Hedef kliniğe giriş
        $transactionService->createTransaction([
            'transaction_number' => $this->generateTransactionNumber(),
            'stock_id' => $targetStock->id,
            'clinic_id' => $request->requester_clinic_id,
            'type' => 'transfer_in',
            'quantity' => $quantity,
            'previous_stock' => $targetStock->current_stock,
            'new_stock' => $targetStock->current_stock + $quantity,
            'stock_request_id' => $request->id,
            'description' => "Transfer from {$request->requestedFromClinic->name}",
            'performed_by' => $performedBy,
            'transaction_date' => now()
        ]);

        // Hedef stoku güncelle
        $stockRepository->update($targetStock->id, [
            'current_stock' => $targetStock->current_stock + $quantity,
            'available_stock' => $targetStock->available_stock + $quantity
        ]);
    }

    protected function findOrCreateTargetStock($sourceStock, int $targetClinicId)
    {
        $stockRepository = app(StockRepositoryInterface::class);

        // ÖNCE: Aynı code'a sahip stok var mı kontrol et
        $existingStock = $stockRepository->findByCode($sourceStock->code);
        if ($existingStock && $existingStock->clinic_id == $targetClinicId) {
            return $existingStock;
        }

        // Aynı ürünün hedef klinikteki karşılığını bul (name + brand ile)
        $targetStock = $stockRepository->findByClinicAndProduct(
            $targetClinicId,
            $sourceStock->name,
            $sourceStock->brand
        );

        if (!$targetStock) {
            // Yeni stok kaydı oluştur - YENİ UNIQUE CODE İLE
            $stockData = $sourceStock->toArray();
            unset($stockData['id'], $stockData['created_at'], $stockData['updated_at']);
            $stockData['clinic_id'] = $targetClinicId;
            $stockData['current_stock'] = 0;
            $stockData['reserved_stock'] = 0;
            $stockData['available_stock'] = 0;
            $stockData['internal_usage_count'] = 0;

            // YENİ KOD OLUŞTUR
            $stockData['code'] = $this->generateUniqueStockCode($sourceStock->category);

            $targetStock = $stockRepository->create($stockData);
        }

        return $targetStock;
    }

    protected function generateUniqueStockCode(string $category): string
    {
        $prefix = strtoupper(substr($category, 0, 3));

        do {
            $sequence = DB::table('stocks')
                         ->where('code', 'like', $prefix . '-%')
                         ->count() + 1;

            $code = $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Benzersiz olup olmadığını kontrol et
            $exists = DB::table('stocks')->where('code', $code)->exists();

        } while ($exists);

        return $code;
    }

    public function getPendingRequests(int $clinicId = null): Collection
    {
        return $this->stockRequestRepository->getPendingRequests($clinicId);
    }

    public function getRequestsByClinic(int $clinicId, string $type = 'all'): Collection
    {
        return $this->stockRequestRepository->getRequestsByClinic($clinicId, $type);
    }
}