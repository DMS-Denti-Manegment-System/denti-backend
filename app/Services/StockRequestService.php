<?php
// app/Modules/Stock/Services/StockRequestService.php

namespace App\Services;

use App\Repositories\Interfaces\StockRequestRepositoryInterface;
use App\Repositories\Interfaces\StockRepositoryInterface;
use App\Services\StockTransactionService;
use App\Models\StockRequest;
use App\Jobs\SendStockRequestNotificationJob;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    public function getAllRequests()
    {
        return $this->stockRequestRepository->all();
    }

    public function getAllWithFilters(array $filters)
    {
        return $this->stockRequestRepository->getAllWithFilters($filters);
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

    /**
     * Stok talebini onayla.
     * Sadece Company Owner veya Super Admin rolüne sahip kullanıcılar onaylayabilir.
     *
     * @throws AuthorizationException   Yetki yoksa
     * @throws \Exception               Talep geçersizse veya stok yetersizse
     */
    public function approveRequest(int $requestId, int $approvedQuantity, string $approvedBy, string $notes = null): bool
    {
        $request = $this->stockRequestRepository->find($requestId);
        if (!$request || $request->status !== 'pending') {
            throw new \Exception('Geçersiz talep veya talep zaten işlenmiş');
        }

        // 🔒 GÜVENLİK: Sadece ürünü VERECEK olan klinik onaylayabilir.
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        
        if (!$isSuperAdmin && $request->requested_from_clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Bu talebi sadece ürünü gönderecek olan klinik onaylayabilir.');
        }

        // Yetki kontrolü (Rol bazlı)
        if (!$isSuperAdmin && !$user->hasAnyRole(['Company Owner', 'Stock Manager', 'Clinic Manager', 'Admin'])) {
            throw new AuthorizationException('Bu talebi onaylama yetkiniz bulunmamaktadır.');
        }

        return DB::transaction(function () use ($request, $requestId, $approvedQuantity, $approvedBy, $notes) {
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

    public function completeRequest(int $requestId, string $performedBy): bool
    {
        $request = $this->stockRequestRepository->find($requestId);
        if (!$request || $request->status !== 'approved') {
            throw new \Exception('Talep bulunamadı veya onaylanmamış');
        }

        // 🔒 GÜVENLİK: Sadece ürünü GÖNDEREN klinik transferi tamamlayabilir.
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        if (!$isSuperAdmin && $request->requested_from_clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Bu işlemi sadece ürünü gönderen klinik tamamlayabilir.');
        }

        if (!$isSuperAdmin && !$user->hasAnyRole(['Company Owner', 'Stock Manager', 'Clinic Manager', 'Admin'])) {
            throw new AuthorizationException('Bu talebi tamamlama yetkiniz bulunmamaktadır.');
        }

        return DB::transaction(function () use ($request, $requestId, $performedBy) {
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

        // 🔒 GÜVENLİK: Sadece ürünü VERECEK olan klinik reddedebilir.
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        if (!$isSuperAdmin && $request->requested_from_clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Bu talebi sadece ürünü gönderecek olan klinik reddedebilir.');
        }

        return (bool) $this->stockRequestRepository->update($requestId, [
            'status' => 'rejected',
            'rejection_reason' => $rejectionReason,
            'approved_by' => $rejectedBy,
            'approved_at' => now()
        ]);
    }

    /**
     * Benzersiz talep numarası üretir (UUID suffix ile çakışma önlendi).
     */
    protected function generateRequestNumber(): string
    {
        $date = now()->format('Ymd');
        return 'REQ-' . $date . '-' . strtoupper(substr(Str::uuid()->toString(), 0, 8));
    }

    /**
     * Benzersiz işlem numarası üretir (UUID suffix ile çakışma önlendi).
     */
    protected function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        return 'TXN-' . $date . '-' . strtoupper(substr(Str::uuid()->toString(), 0, 8));
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

        // Kaynak klinikten çıkış işlemi (Observer current_stock'u düşürecek)
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

        // 🛡️ Sadece rezerve stoku güncelle (Observer sadece current_stock'u yönetir)
        $stockRepository->update($sourceStock->id, [
            'reserved_stock' => $sourceStock->reserved_stock - $quantity,
        ]);

        // Hedef klinikteki stoku bul veya oluştur
        $targetStock = $this->findOrCreateTargetStock($sourceStock, $request->requester_clinic_id);

        // Hedef kliniğe giriş işlemi (Observer current_stock'u artıracak)
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

        // Hedef stokta rezerve güncellemesine gerek yok (yeni giriş)
    }

    protected function findOrCreateTargetStock($sourceStock, int $targetClinicId)
    {
        $stockRepository = app(StockRepositoryInterface::class);

        // ÖNCE: Hedef klinikte bu ürüne ait bir stok var mı kontrol et
        $existingStock = $stockRepository->getBaseQuery()
            ->where('product_id', $sourceStock->product_id)
            ->where('clinic_id', $targetClinicId)
            ->first();

        if ($existingStock) {
            return $existingStock;
        }

        // Aynı ürünün hedef klinikteki karşılığını bul (Artık product_id ile yapıyoruz)
        // Eğer yukarıdaki kontrol bulamadıysa, yeni bir tane oluşturacağız
        $targetStock = $existingStock;

        if (!$targetStock) {
            // Yeni stok kaydı oluştur
            $stockData = $sourceStock->toArray();
            unset($stockData['id'], $stockData['created_at'], $stockData['updated_at']);
            $stockData['clinic_id'] = $targetClinicId;
            $stockData['current_stock'] = 0;
            $stockData['reserved_stock'] = 0;
            $stockData['available_stock'] = 0;
            $stockData['internal_usage_count'] = 0;

            $targetStock = $stockRepository->create($stockData);
        }

        return $targetStock;
    }

    public function getPendingRequests(int $clinicId = null): Collection
    {
        return $this->stockRequestRepository->getPendingRequests($clinicId);
    }

    public function getRequestsByClinic(int $clinicId, string $type = 'all'): Collection
    {
        return $this->stockRequestRepository->getRequestsByClinic($clinicId, $type);
    }

    public function getRequestStats(): array
    {
        return $this->stockRequestRepository->getStats();
    }
}