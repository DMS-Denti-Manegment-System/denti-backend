<?php
// app/Modules/Stock/Controllers/StockController.php

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Requests\StoreStockRequest;
use App\Modules\Stock\Requests\UpdateStockRequest;
use App\Exceptions\Stock\StockNotFoundException;
use App\Exceptions\Stock\InsufficientStockException;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Stock\Resources\StockResource;

class StockController extends Controller
{
    use JsonResponseTrait;

    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'clinic_id', 'supplier_id', 'category', 'status',
                'stock_status', 'search', 'expiry_filter', 'name'
            ]);

            $stocks = $this->stockService->getAllStocks($filters);

            return $this->success(StockResource::collection($stocks));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int)$id);

            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            return $this->success(new StockResource($stock));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function store(StoreStockRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $data['yellow_alert_level'] = $data['yellow_alert_level'] ?? $data['min_stock_level'];
            $data['red_alert_level'] = $data['red_alert_level'] ?? $data['critical_stock_level'];
            $data['currency'] = $data['currency'] ?? 'TRY';
            $data['is_active'] = $data['is_active'] ?? true;
            $data['status'] = $data['is_active'] ? 'active' : 'inactive';
            $data['track_expiry'] = $data['track_expiry'] ?? true;
            $data['track_batch'] = $data['track_batch'] ?? false;

            $stock = $this->stockService->createStock($data);

            return $this->success(new StockResource($stock), 'Stok başarıyla oluşturuldu', 201);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function update(UpdateStockRequest $request, $id): JsonResponse
    {
        try {
            $data = $request->validated();

            if (isset($data['is_active'])) {
                $data['status'] = $data['is_active'] ? 'active' : 'inactive';
            }

            $stock = $this->stockService->updateStock((int)$id, $data);

            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            return $this->success(new StockResource($stock), 'Stok başarıyla güncellendi');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $deleted = $this->stockService->deleteStock((int)$id);

            if (!$deleted) {
                return $this->error('Stok bulunamadı', 404);
            }

            return $this->success(null, 'Stok başarıyla silindi');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function adjustStock(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type'        => 'required|in:increase,decrease',
            'quantity'    => 'required|integer|min:1',
            'reason'      => 'required|string|max:500',
            // performed_by güvenlik düzeltmesi: artık client'tan alınmıyor, sunucudan üretiliyor
            'notes'       => 'nullable|string|max:1000',
            'is_sub_unit' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        try {
            $data        = $validator->validated();
            $quantity    = $data['type'] === 'increase' ? $data['quantity'] : -$data['quantity'];
            $isSubUnit   = $data['is_sub_unit'] ?? false;
            // 🔒 Güvenlik: Kim yaptı bilgisini asla client'tan alma, oturum'dan al
            $performedBy = auth()->user()->name;

            $this->stockService->adjustStock(
                (int)$id,
                $quantity,
                $data['reason'],
                $performedBy,
                $isSubUnit
            );

            return $this->success(new StockResource($this->stockService->getStockById((int)$id)), 'Stok başarıyla düzeltildi');
        } catch (StockNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (InsufficientStockException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function useStock(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'reason'   => 'required|string|max:500',
            // performed_by güvenlik düzeltmesi: artık client'tan alınmıyor
            'used_by'  => 'nullable|string|max:255',
            'notes'    => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors());
        }

        try {
            $data  = $validator->validated();
            $notes = $data['notes'] ?? '';
            if (!empty($data['used_by'])) {
                $notes .= "\nKullanan: " . $data['used_by'];
            }
            if (!empty($data['reason'])) {
                $notes .= "\nSebep: " . $data['reason'];
            }

            // 🔒 Güvenlik: Kim yaptı bilgisini asla client'tan alma, oturum'dan al
            $performedBy = auth()->user()->name;

            $this->stockService->useStock(
                (int)$id,
                $data['quantity'],
                $performedBy,
                $notes
            );

            return $this->success(new StockResource($this->stockService->getStockById((int)$id)), 'Stok kullanımı kaydedildi');
        } catch (StockNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (InsufficientStockException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getLowLevel(Request $request): JsonResponse
    {
        try {
            $clinicId = $request->query('clinic_id');
            $items = $this->stockService->getLowStockItems($clinicId ? (int)$clinicId : null);

            return $this->success(StockResource::collection($items));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getCriticalLevel(Request $request): JsonResponse
    {
        try {
            $clinicId = $request->query('clinic_id');
            $items = $this->stockService->getCriticalStockItems($clinicId ? (int)$clinicId : null);

            return $this->success(StockResource::collection($items));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getExpiring(Request $request): JsonResponse
    {
        try {
            $days = $request->query('days', 30);
            $clinicId = $request->query('clinic_id');
            $items = $this->stockService->getExpiringItems((int)$days, $clinicId ? (int)$clinicId : null);

            return $this->success(StockResource::collection($items));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getStats(Request $request): JsonResponse
    {
        try {
            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;
            
            $stats = $this->stockService->getStockStats($companyId, $clinicId ? (int)$clinicId : null);

            return $this->success($stats);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function forceDelete($id): JsonResponse
    {
        try {
            $deleted = $this->stockService->forceDeleteStock((int)$id);

            if (!$deleted) {
                return $this->error('Stok bulunamadı', 404);
            }

            return $this->success(null, 'Stok kalıcı olarak silindi');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function deactivate($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int)$id);

            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $updatedStock = $this->stockService->updateStock((int)$id, [
                'is_active' => false,
                'status' => 'inactive'
            ]);

            return $this->success(new StockResource($updatedStock), 'Stok pasif duruma getirildi');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function reactivate($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int)$id);

            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $updatedStock = $this->stockService->updateStock((int)$id, [
                'is_active' => true,
                'status' => 'active'
            ]);

            return $this->success(new StockResource($updatedStock), 'Stok tekrar aktif edildi');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }
}
