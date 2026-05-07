<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Stock;
use App\Models\StockTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        ])
            ->where('company_id', $user->company_id);

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
            'stock_id' => 'required|exists:stocks,id',
            'to_clinic_id' => 'required|exists:clinics,id|different:from_clinic_id',
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

        // Yeterli stok kontrolü
        if ($stock->current_stock < $validated['quantity']) {
            return $this->error(
                "Yetersiz stok. Mevcut: {$stock->current_stock}, İstenen: {$validated['quantity']}",
                422
            );
        }

        // Hedef klinik aynı şirkette mi?
        $toClinic = Clinic::findOrFail($validated['to_clinic_id']);
        if ($toClinic->company_id !== $user->company_id) {
            return $this->error('Hedef klinik farklı bir şirkete ait.', 403);
        }

        try {
            DB::beginTransaction();

            $transfer = StockTransfer::create([
                'product_id' => $stock->product_id,
                'stock_id' => $stock->id,
                'from_clinic_id' => $stock->clinic_id,
                'to_clinic_id' => $toClinic->id,
                'company_id' => $user->company_id,
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'],
                'status' => StockTransfer::STATUS_PENDING,
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);

            // Stok rezerve et (opsiyonel - stok miktarını düşürme, sadece ayırma)
            // $stock->reserved_stock += $validated['quantity'];
            // $stock->save();

            DB::commit();

            // Bildirim gönder (Queue)
            // TODO: Transfer isteği bildirimi (hedef klinik admin'ine)

            return $this->success(
                $transfer->load(['product', 'stock', 'fromClinic', 'toClinic', 'requestedBy']),
                'Transfer isteği oluşturuldu.',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

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
            ->where('company_id', $user->company_id)
            ->findOrFail($id);

        return $this->success($transfer, 'Transfer details retrieved.');
    }

    /**
     * Transfer onayla (hedef klinik yetkilisi)
     */
    public function approve(int $id): JsonResponse
    {
        $user = Auth::user();

        $transfer = StockTransfer::where('company_id', $user->company_id)
            ->findOrFail($id);

        // Yetki kontrolü: Hedef klinik yetkilisi mi?
        if ($transfer->to_clinic_id !== $user->clinic_id && ! $user->hasPermissionTo('approve-transfers')) {
            if (is_null($user->clinic_id)) {
                return $this->error('Kullanıcıya atanmış bir klinik bulunmuyor. Lütfen yöneticinize başvurun.', 403);
            }

            return $this->error('Bu transferi onaylama yetkiniz yok.', 403);
        }

        if (! $transfer->canApprove()) {
            return $this->error('Bu transfer onaylanamaz. Durum: '.$transfer->status_label, 422);
        }

        $stock = Stock::findOrFail($transfer->stock_id);

        // Yeterli stok kontrolü
        if ($stock->current_stock < $transfer->quantity) {
            return $this->error(
                'Kaynak stok yetersiz. Transfer edilemez.',
                422
            );
        }

        try {
            DB::beginTransaction();

            // Transfer kaydını güncelle
            $transfer->update([
                'status' => StockTransfer::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Kaynak stoktan düş
            $stock->current_stock -= $transfer->quantity;
            $stock->save();

            // Hedef klinikte stok oluştur veya güncelle
            $targetStock = Stock::firstOrCreate(
                [
                    'product_id' => $transfer->product_id,
                    'clinic_id' => $transfer->to_clinic_id,
                    'company_id' => $transfer->company_id,
                    'batch_code' => $stock->batch_code,
                ],
                [
                    'supplier_id' => $stock->supplier_id,
                    'purchase_price' => $stock->purchase_price,
                    'currency' => $stock->currency,
                    'purchase_date' => $stock->purchase_date,
                    'expiry_date' => $stock->expiry_date,
                    'current_stock' => 0,
                    'has_sub_unit' => $stock->has_sub_unit,
                    'sub_unit_multiplier' => $stock->sub_unit_multiplier,
                    'sub_unit_name' => $stock->sub_unit_name,
                    'is_active' => true,
                ]
            );

            // Hedef stoğu artır
            $targetStock->current_stock += $transfer->quantity;
            $targetStock->save();

            // Stok hareket kaydı oluştur (opsiyonel)
            // TODO: StockTransaction kaydı

            // Transfer tamamlandı olarak işaretle
            $transfer->update([
                'status' => StockTransfer::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

            DB::commit();

            // Bildirim gönder
            // TODO: Transfer tamamlandı bildirimi

            return $this->success(
                $transfer->fresh(['product', 'stock', 'fromClinic', 'toClinic']),
                'Transfer onaylandı ve tamamlandı.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

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

        $transfer = StockTransfer::where('company_id', $user->company_id)
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

        $transfer->update([
            'status' => StockTransfer::STATUS_REJECTED,
            'rejection_reason' => $validated['reason'],
            'approved_by' => $user->id,  // Reddeden kişi
            'approved_at' => now(),
        ]);

        // Rezerve edilen stoğu serbest bırak (eğer rezervasyon varsa)
        // $stock = Stock::find($transfer->stock_id);
        // $stock->reserved_stock -= $transfer->quantity;
        // $stock->save();

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

        $transfer = StockTransfer::where('company_id', $user->company_id)
            ->findOrFail($id);

        // Yetki kontrolü: Sadece isteyen veya admin
        if ($transfer->requested_by !== $user->id && ! $user->hasPermissionTo('cancel-transfers')) {
            return $this->error('Bu transferi iptal etme yetkiniz yok.', 403);
        }

        if (! $transfer->canCancel()) {
            return $this->error('Bu transfer iptal edilemez.', 422);
        }

        $transfer->update([
            'status' => StockTransfer::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        // Rezerve edilen stoğu serbest bırak
        // $stock = Stock::find($transfer->stock_id);
        // $stock->reserved_stock -= $transfer->quantity;
        // $stock->save();

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
}
