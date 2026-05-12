<?php

// app/Modules/Stock/Services/StockRequestService.php

namespace App\Services;

use App\Jobs\SendStockRequestNotificationJob;
use App\Models\Stock;
use App\Models\StockRequest;
use App\Repositories\Interfaces\StockRequestRepositoryInterface;
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

    public function getAllWithFilters(array $filters, int $perPage = 15)
    {
        return $this->stockRequestRepository->getAllWithFilters($filters, $perPage);
    }

    // ✅ EKSİK METOD EKLENDİ
    public function getRequestById(int $id): ?StockRequest
    {
        return $this->stockRequestRepository->find($id);
    }

    public function createRequest(array $data): StockRequest
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole('Admin') && (int) $data['requester_clinic_id'] !== (int) $user->clinic_id) {
            throw new AuthorizationException('Sadece kendi kliniğiniz adına talep oluşturabilirsiniz.');
        }

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
     * Sadece yetkili stok/klinik yöneticileri onaylayabilir.
     *
     * @throws AuthorizationException Yetki yoksa
     * @throws \Exception Talep geçersizse veya stok yetersizse
     */
    public function approveRequest(int $requestId, int $approvedQuantity, string $approvedBy, ?string $notes = null): bool
    {
        $request = StockRequest::with(['stock'])->find($requestId);
        if (! $request || $request->status !== 'pending') {
            throw new \Exception('Geçersiz talep veya talep zaten işlenmiş');
        }

        // 🔒 GÜVENLİK: Sadece ürünü VERECEK olan klinik veya ADMIN onaylayabilir.
        $user = Auth::user();
        $isAdmin = $user->hasRole('Admin');
        if (! $isAdmin && $request->requested_from_clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Bu talebi sadece ürünü gönderecek olan klinik onaylayabilir.');
        }

        // Yetki kontrolü (Rol bazlı)
        if (! $user->hasAnyRole(['Stock Manager', 'Clinic Manager', 'Admin'])) {
            throw new AuthorizationException('Bu talebi onaylama yetkiniz bulunmamaktadır.');
        }

        return DB::transaction(function () use ($requestId, $approvedQuantity, $approvedBy, $notes) {
            $request = StockRequest::whereKey($requestId)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw new \Exception('Geçersiz talep veya talep zaten işlenmiş');
            }

            $stock = Stock::whereKey($request->stock_id)->lockForUpdate()->firstOrFail();
            if ($stock->available_stock < $approvedQuantity) {
                throw new \Exception('Yetersiz stok miktarı');
            }

            $request->update([
                'status' => 'approved',
                'approved_quantity' => $approvedQuantity,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'admin_notes' => $notes,
            ]);

            $this->reserveLockedStock($stock, $approvedQuantity);

            return true;
        });
    }

    public function shipRequest(int $requestId, string $performedBy): bool
    {
        $request = StockRequest::find($requestId);
        if (! $request || $request->status !== 'approved') {
            throw new \Exception('Talep bulunamadı veya onaylanmamış');
        }

        // GÜVENLİK: Sadece ürünü GÖNDEREN klinik veya ADMIN transferi başlatabilir.
        $user = Auth::user();
        $isAdmin = $user->hasRole('Admin');
        if (! $isAdmin && $request->requested_from_clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Bu işlemi sadece ürünü gönderen klinik başlatabilir.');
        }

        return DB::transaction(function () use ($requestId) {
            $request = StockRequest::whereKey($requestId)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'approved') {
                throw new \Exception('Talep bulunamadı veya onaylanmamış');
            }

            return $request->update([
                'status' => 'in_transit',
                'updated_at' => now(),
            ]);
        });
    }

    public function completeRequest(int $requestId, string $performedBy): bool
    {
        $request = $this->stockRequestRepository->find($requestId);
        if (! $request || $request->status !== 'in_transit') {
            throw new \Exception('Talep bulunamadı veya transfer sürecinde değil');
        }

        // 🔒 GÜVENLİK: Sadece ürünü ALAN klinik veya ADMIN transferi tamamlayabilir.
        $user = Auth::user();
        $isAdmin = $user->hasRole('Admin');
        if (! $isAdmin && $request->requester_clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Bu işlemi sadece ürünü teslim alan klinik tamamlayabilir.');
        }

        if (! $user->hasAnyRole(['Stock Manager', 'Clinic Manager', 'Admin'])) {
            throw new AuthorizationException('Bu talebi tamamlama yetkiniz bulunmamaktadır.');
        }

        return DB::transaction(function () use ($requestId, $performedBy) {
            $request = StockRequest::whereKey($requestId)
                ->with(['requesterClinic', 'requestedFromClinic'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'in_transit') {
                throw new \Exception('Talep bulunamadı veya transfer sürecinde değil');
            }

            $this->transferStock($request, $performedBy);

            $request->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return true;
        });
    }

    public function rejectRequest(int $requestId, string $rejectionReason, string $rejectedBy): bool
    {
        $request = StockRequest::find($requestId);
        if (! $request || $request->status !== 'pending') {
            throw new \Exception('Talep bulunamadı veya zaten işlenmiş');
        }

        // 🔒 GÜVENLİK: Sadece ürünü VERECEK olan klinik veya ADMIN reddedebilir.
        $user = Auth::user();
        $isAdmin = $user->hasRole('Admin');
        if (! $isAdmin && $request->requested_from_clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Bu talebi sadece ürünü gönderecek olan klinik reddedebilir.');
        }

        return DB::transaction(function () use ($requestId, $rejectionReason, $rejectedBy) {
            $request = StockRequest::whereKey($requestId)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw new \Exception('Talep bulunamadı veya zaten işlenmiş');
            }

            return (bool) $request->update([
                'status' => 'rejected',
                'rejection_reason' => $rejectionReason,
                'approved_by' => $rejectedBy,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Benzersiz talep numarası üretir (UUID suffix ile çakışma önlendi).
     */
    protected function generateRequestNumber(): string
    {
        $date = now()->format('Ymd');

        return 'REQ-'.$date.'-'.strtoupper(substr(Str::uuid()->toString(), 0, 8));
    }

    /**
     * Benzersiz işlem numarası üretir (UUID suffix ile çakışma önlendi).
     */
    protected function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');

        return 'TXN-'.$date.'-'.strtoupper(substr(Str::uuid()->toString(), 0, 8));
    }

    // ✅ HATA DÜZELTİLDİ: Proper interface resolution
    protected function reserveStock(int $stockId, int $quantity): void
    {
        $stock = Stock::whereKey($stockId)->lockForUpdate()->first();

        if ($stock) {
            $this->reserveLockedStock($stock, $quantity);
        }
    }

    protected function reserveLockedStock(Stock $stock, int $quantity): void
    {
        $stock->update([
            'reserved_stock' => $stock->reserved_stock + $quantity,
            'available_stock' => $stock->current_stock - ($stock->reserved_stock + $quantity),
        ]);
    }

    // ✅ HATA DÜZELTİLDİ: Proper service resolution
    protected function transferStock(StockRequest $request, string $performedBy): void
    {
        $sourceStock = Stock::whereKey($request->stock_id)->lockForUpdate()->firstOrFail();
        $quantity = $request->approved_quantity;
        $transactionService = app(StockTransactionService::class);

        if ($sourceStock->reserved_stock < $quantity || $sourceStock->current_stock < $quantity) {
            throw new \Exception('Transfer için yeterli rezerve stok yok.');
        }

        // 1. Giden Transfer İşlemi (Source Clinic)
        $txnOut = $transactionService->createTransaction([
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
            'transaction_date' => now(),
        ]);

        // Stok miktarını düşür (Physical Stock)
        $this->stockService->applyTransactionToStock($txnOut);

        // Rezerve stoğu serbest bırak
        $sourceStock->refresh();
        $sourceStock->updateQuietly([
            'reserved_stock' => max(0, $sourceStock->reserved_stock - $quantity),
            'available_stock' => $sourceStock->current_stock - max(0, $sourceStock->reserved_stock - $quantity),
        ]);

        // 2. Gelen Transfer İşlemi (Target Clinic)
        $targetStock = $this->findOrCreateTargetStock($sourceStock, $request->requester_clinic_id);

        $txnIn = $transactionService->createTransaction([
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
            'transaction_date' => now(),
        ]);

        // Stok miktarını arttır (Physical Stock)
        $this->stockService->applyTransactionToStock($txnIn);
    }

    protected function findOrCreateTargetStock($sourceStock, int $targetClinicId)
    {
        $existingStock = Stock::query()
            ->where('product_id', $sourceStock->product_id)
            ->where('clinic_id', $targetClinicId)
            ->lockForUpdate()
            ->first();

        if ($existingStock) {
            return $existingStock;
        }

        $stockData = $sourceStock->toArray();
        unset($stockData['id'], $stockData['created_at'], $stockData['updated_at'], $stockData['deleted_at']);
        $stockData['clinic_id'] = $targetClinicId;
        $stockData['current_stock'] = 0;
        $stockData['reserved_stock'] = 0;
        $stockData['available_stock'] = 0;
        $stockData['internal_usage_count'] = 0;

        return Stock::create($stockData);
    }

    public function getPendingRequests(?int $clinicId = null): Collection
    {
        return $this->stockRequestRepository->getPendingRequests($clinicId);
    }

    public function getRequestsByClinic(int $clinicId, string $type = 'all'): Collection
    {
        return $this->stockRequestRepository->getRequestsByClinic($clinicId, $type);
    }

    public function getRequestStats(?int $clinicId = null): array
    {
        return $this->stockRequestRepository->getStats($clinicId);
    }
}
