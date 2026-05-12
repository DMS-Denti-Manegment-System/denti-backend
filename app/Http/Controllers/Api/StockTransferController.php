<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Services\StockTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockTransferController extends Controller
{
    /**
     * Transfer listesi (şirket bazlı)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $clinicId = $request->get('clinic_id');
        $type = $request->get('type', 'all'); // all, incoming, outgoing
        $status = $request->get('status');

        $query = StockTransfer::with([
            'product:id,name,sku,unit',
            'stock:id,batch_code,current_stock',
            'fromClinic:id,name',
            'toClinic:id,name',
            'requestedBy:id,name',
            'approvedBy:id,name',
        ]);

        // Klinik filtresi
        if ($clinicId) {
            if ($type === 'incoming') {
                $query->incoming($clinicId);
            } elseif ($type === 'outgoing') {
                $query->outgoing($clinicId);
            } else {
                $query->forClinic($clinicId);
            }
        }

        // Status filtresi
        if ($status) {
            $query->where('status', $status);
        }

        // Sıralama
        $query->orderByDesc('requested_at');

        $transfers = $query->paginate($request->get('per_page', 15));

        return $this->success($transfers, 'Transfers retrieved successfully.');
    }

    /**
     * Yeni transfer isteği oluştur
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stock_id' => [
                'required',
                Rule::exists('stocks', 'id'),
            ],
            'to_clinic_id' => [
                'required',
                Rule::exists('clinics', 'id'),
            ],
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $stock = Stock::with('product')->findOrFail($validated['stock_id']);

        // Yetki kontrolü: Kaynak klinikte stok görme yetkisi
        if ($stock->clinic_id !== $user->clinic_id && ! $user->hasPermissionTo('transfer-stocks')) {
            \Log::warning('Transfer yetki hatası', [
                'user_id' => $user->id,
                'user_clinic_id' => $user->clinic_id,
                'stock_clinic_id' => $stock->clinic_id,
                'stock_id' => $stock->id,
                'has_transfer_permission' => $user->hasPermissionTo('transfer-stocks'),
            ]);
            if (is_null($user->clinic_id)) {
                return $this->error('Kullanıcıya atanmış bir klinik bulunmuyor. Lütfen yöneticinize başvurun.', 403);
            }

            return $this->error('Bu stok için transfer yetkiniz yok.', 403);
        }

        if ($stock->clinic_id === (int) $validated['to_clinic_id']) {
            return $this->error('Hedef klinik kaynak klinik ile aynı olamaz.', 422);
        }

        if ($stock->available_stock < $validated['quantity']) {
            return $this->error(
                "Yetersiz stok. Kullanılabilir: {$stock->available_stock}, İstenen: {$validated['quantity']}",
                422
            );
        }

        $toClinic = Clinic::findOrFail($validated['to_clinic_id']);

        try {
            $transfer = DB::transaction(function () use ($stock, $toClinic, $validated, $user) {
                $lockedStock = Stock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
                if ($lockedStock->available_stock < $validated['quantity']) {
                    throw new \RuntimeException('Yetersiz stok.');
                }

                $lockedStock->update([
                    'reserved_stock' => $lockedStock->reserved_stock + $validated['quantity'],
                    'available_stock' => $lockedStock->current_stock - ($lockedStock->reserved_stock + $validated['quantity']),
                ]);

                return StockTransfer::create([
                    'product_id' => $lockedStock->product_id,
                    'stock_id' => $lockedStock->id,
                    'from_clinic_id' => $lockedStock->clinic_id,
                    'to_clinic_id' => $toClinic->id,
                    'quantity' => $validated['quantity'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => StockTransfer::STATUS_PENDING,
                    'requested_by' => $user->id,
                    'requested_at' => now(),
                ]);
            });

            return $this->success(
                $transfer->load(['product', 'stock', 'fromClinic', 'toClinic', 'requestedBy']),
                'Transfer isteği oluşturuldu.',
                201
            );

        } catch (\Exception $e) {
            return $this->error('Transfer oluşturulurken hata: '.$e->getMessage(), 500);
        }
    }

    /**
     * Transfer detayı
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $transfer = StockTransfer::with([
            'product:id,name,sku,unit',
            'stock:id,batch_code,current_stock,expiry_date',
            'fromClinic:id,name',
            'toClinic:id,name',
            'requestedBy:id,name,email',
            'approvedBy:id,name,email',
            'completedBy:id,name,email',
        ])
            ->findOrFail($id);

        return $this->success($transfer, 'Transfer details retrieved.');
    }

    /**
     * Transfer onayla (hedef klinik yetkilisi)
     */
    public function approve(int $id): JsonResponse
    {
        $user = Auth::user();

        $transfer = StockTransfer::query()->findOrFail($id);

        // Yetki kontrolü: Hedef klinik yetkilisi mi?
        if ($transfer->to_clinic_id !== $user->clinic_id && ! $user->hasPermissionTo('approve-transfers')) {
            if (is_null($user->clinic_id)) {
                return $this->error('Kullanıcıya atanmış bir klinik bulunmuyor. Lütfen yöneticinize başvurun.', 403);
            }

            return $this->error('Bu transferi onaylama yetkiniz yok.', 403);
        }

        try {
            $transfer = DB::transaction(function () use ($id, $user) {
                $transfer = StockTransfer::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $transfer->canApprove()) {
                    throw new \RuntimeException('Bu transfer onaylanamaz. Durum: '.$transfer->status_label);
                }

                $stock = Stock::whereKey($transfer->stock_id)->lockForUpdate()->firstOrFail();
                if ($stock->current_stock < $transfer->quantity || $stock->reserved_stock < $transfer->quantity) {
                    throw new \RuntimeException('Kaynak stok yetersiz. Transfer edilemez.');
                }

                $transfer->update([
                    'status' => StockTransfer::STATUS_APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                $targetStock = $this->findOrCreateTargetStock($stock, $transfer->to_clinic_id);
                $transactionService = app(StockTransactionService::class);

                $transactionService->createTransaction([
                    'transaction_number' => $this->generateTransactionNumber(),
                    'stock_id' => $stock->id,
                    'clinic_id' => $transfer->from_clinic_id,
                    'type' => 'transfer_out',
                    'quantity' => $transfer->quantity,
                    'previous_stock' => $stock->current_stock,
                    'new_stock' => $stock->current_stock - $transfer->quantity,
                    'description' => "Transfer to {$transfer->toClinic?->name}",
                    'performed_by' => $user->name,
                    'user_id' => $user->id,
                    'transaction_date' => now(),
                ]);

                $stock->refresh();
                $stock->update([
                    'reserved_stock' => $stock->reserved_stock - $transfer->quantity,
                    'available_stock' => $stock->current_stock - ($stock->reserved_stock - $transfer->quantity),
                ]);

                $transactionService->createTransaction([
                    'transaction_number' => $this->generateTransactionNumber(),
                    'stock_id' => $targetStock->id,
                    'clinic_id' => $transfer->to_clinic_id,
                    'type' => 'transfer_in',
                    'quantity' => $transfer->quantity,
                    'previous_stock' => $targetStock->current_stock,
                    'new_stock' => $targetStock->current_stock + $transfer->quantity,
                    'description' => "Transfer from {$transfer->fromClinic?->name}",
                    'performed_by' => $user->name,
                    'user_id' => $user->id,
                    'transaction_date' => now(),
                ]);

                $transfer->update([
                    'status' => StockTransfer::STATUS_COMPLETED,
                    'completed_by' => $user->id,
                    'completed_at' => now(),
                ]);

                return $transfer;
            });

            return $this->success(
                $transfer->fresh(['product', 'stock', 'fromClinic', 'toClinic']),
                'Transfer onaylandı ve tamamlandı.'
            );

        } catch (\Exception $e) {
            return $this->error('Transfer onaylanırken hata: '.$e->getMessage(), 500);
        }
    }

    /**
     * Transfer reddet
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $user = Auth::user();

        $transfer = StockTransfer::query()
            ->findOrFail($id);

        // Yetki kontrolü
        if ($transfer->to_clinic_id !== $user->clinic_id && ! $user->hasPermissionTo('approve-transfers')) {
            if (is_null($user->clinic_id)) {
                return $this->error('Kullanıcıya atanmış bir klinik bulunmuyor. Lütfen yöneticinize başvurun.', 403);
            }

            return $this->error('Bu transferi reddetme yetkiniz yok.', 403);
        }

        if (! $transfer->canReject()) {
            return $this->error('Bu transfer reddedilemez.', 422);
        }

        DB::transaction(function () use ($transfer, $validated, $user) {
            $lockedTransfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if (! $lockedTransfer->canReject()) {
                throw new \RuntimeException('Bu transfer reddedilemez.');
            }

            $stock = Stock::whereKey($lockedTransfer->stock_id)->lockForUpdate()->firstOrFail();
            $this->releaseReservedStock($stock, $lockedTransfer->quantity);

            $lockedTransfer->update([
                'status' => StockTransfer::STATUS_REJECTED,
                'rejection_reason' => $validated['reason'],
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        });

        // Bildirim gönder
        // TODO: Transfer reddedildi bildirimi (isteyen kişiye)

        return $this->success($transfer, 'Transfer reddedildi.');
    }

    /**
     * Transfer iptal et (isteyen kişi)
     */
    public function cancel(int $id): JsonResponse
    {
        $user = Auth::user();

        $transfer = StockTransfer::query()
            ->findOrFail($id);

        // Yetki kontrolü: Sadece isteyen veya admin
        if ($transfer->requested_by !== $user->id && ! $user->hasPermissionTo('cancel-transfers')) {
            return $this->error('Bu transferi iptal etme yetkiniz yok.', 403);
        }

        if (! $transfer->canCancel()) {
            return $this->error('Bu transfer iptal edilemez.', 422);
        }

        DB::transaction(function () use ($transfer) {
            $lockedTransfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            if (! $lockedTransfer->canCancel()) {
                throw new \RuntimeException('Bu transfer iptal edilemez.');
            }

            $stock = Stock::whereKey($lockedTransfer->stock_id)->lockForUpdate()->firstOrFail();
            $this->releaseReservedStock($stock, $lockedTransfer->quantity);

            $lockedTransfer->update([
                'status' => StockTransfer::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
        });

        return $this->success($transfer, 'Transfer iptal edildi.');
    }

    /**
     * Bekleyen transfer sayısı (bildirim için)
     */
    public function getPendingCount(): JsonResponse
    {
        $user = Auth::user();
        $clinicId = $user->clinic_id ?? 'none';
        $cacheKey = "pending_transfers_count_{$user->id}_{$clinicId}";

        $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($user) {
            $incomingCount = StockTransfer::where('to_clinic_id', $user->clinic_id)
                ->where('status', StockTransfer::STATUS_PENDING)
                ->count();

            $outgoingCount = StockTransfer::where('from_clinic_id', $user->clinic_id)
                ->where('status', StockTransfer::STATUS_PENDING)
                ->count();

            return [
                'incoming' => $incomingCount,
                'outgoing' => $outgoingCount,
                'total' => $incomingCount + $outgoingCount,
            ];
        });

        return $this->success($stats, 'Pending transfer counts.');
    }

    private function findOrCreateTargetStock(Stock $sourceStock, int $targetClinicId): Stock
    {
        $targetStock = Stock::where('product_id', $sourceStock->product_id)
            ->where('clinic_id', $targetClinicId)
            ->where('batch_code', $sourceStock->batch_code)
            ->lockForUpdate()
            ->first();

        if ($targetStock) {
            return $targetStock;
        }

        return Stock::create([
            'product_id' => $sourceStock->product_id,
            'batch_code' => $sourceStock->batch_code,
            'supplier_id' => $sourceStock->supplier_id,
            'purchase_price' => $sourceStock->purchase_price,
            'currency' => $sourceStock->currency,
            'purchase_date' => $sourceStock->purchase_date,
            'expiry_date' => $sourceStock->expiry_date,
            'current_stock' => 0,
            'reserved_stock' => 0,
            'available_stock' => 0,
            'clinic_id' => $targetClinicId,
            'has_sub_unit' => $sourceStock->has_sub_unit,
            'sub_unit_multiplier' => $sourceStock->sub_unit_multiplier,
            'sub_unit_name' => $sourceStock->sub_unit_name,
            'is_active' => true,
        ]);
    }

    private function releaseReservedStock(Stock $stock, int $quantity): void
    {
        $reservedStock = max(0, $stock->reserved_stock - $quantity);
        $stock->update([
            'reserved_stock' => $reservedStock,
            'available_stock' => $stock->current_stock - $reservedStock,
        ]);
    }

    private function generateTransactionNumber(): string
    {
        return 'TXN-'.now()->format('Ymd').'-'.strtoupper(Str::random(12));
    }
}
