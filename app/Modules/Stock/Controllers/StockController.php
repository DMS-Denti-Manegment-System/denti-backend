<?php
// app/Modules/Stock/Controllers/StockController.php

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockService;
use App\Modules\Stock\Requests\StoreStockRequest;
use App\Modules\Stock\Requests\UpdateStockRequest;
use App\Modules\Stock\Requests\AdjustStockRequest;
use App\Modules\Stock\Requests\UseStockRequest;
use App\Exceptions\Stock\StockNotFoundException;
use App\Exceptions\Stock\InsufficientStockException;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Modules\Stock\Resources\StockResource;
use App\Modules\Stock\Resources\StockTransactionResource;
use App\Modules\Stock\Models\Stock;

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

            // 🛡️ Güvenlik: Sayfalama limitine üst sınır koy (DDoS koruması)
            $perPage = min((int)$request->query('per_page', 50), 100);

            $this->authorize('viewAny', Stock::class);

            $stocks = $this->stockService->getAllStocks($filters, $perPage);

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

            $this->authorize('view', $stock);

            return $this->success(new StockResource($stock));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function transactions($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int)$id);
            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('view', $stock);

            $transactions = $this->stockService->getStockTransactions((int)$id);

            return $this->success(StockTransactionResource::collection($transactions));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function store(StoreStockRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Set defaults for batch
            $data['company_id'] = auth()->user()->company_id;
            $data['currency'] = $data['currency'] ?? 'TRY';
            $data['track_expiry'] = $data['track_expiry'] ?? true;

            $this->authorize('create', Stock::class);

            $stock = $this->stockService->createStock($data);
            
            // Fix: Load product for Resource naming (avoids undefined/null error)
            $stock->load('product');

            return $this->success(new StockResource($stock), 'Stok başarıyla oluşturuldu', 201);
        } catch (\Exception $e) {
            Log::error('Stock Store Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
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

            $stock = $this->stockService->getStockById((int)$id);
            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('update', $stock);

            $stock = $this->stockService->updateStock((int)$id, $data);

            return $this->success(new StockResource($stock), 'Stok başarıyla güncellendi');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int)$id);
            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('delete', $stock);

            $deleted = $this->stockService->deleteStock((int)$id);

            return $this->success(null, 'Stok başarıyla silindi');
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function adjustStock(AdjustStockRequest $request, $id): JsonResponse
    {
        try {
            $data        = $request->validated();
            $isSubUnit   = $data['is_sub_unit'] ?? false;
            
            $stock = $this->stockService->getStockById((int)$id);
            if (!$stock) {
                throw new StockNotFoundException($id);
            }

            if ($data['type'] === 'sync') {
                $current = $isSubUnit ? $stock->current_sub_stock : $stock->current_stock;
                $quantity = $data['quantity'] - $current;
            } else {
                $quantity = $data['type'] === 'increase' ? $data['quantity'] : -$data['quantity'];
            }
            
            if ($quantity === 0 && $data['type'] === 'sync') {
                return $this->success(new StockResource($stock), 'Stok miktarı zaten hedeflenen seviyede.');
            }

            // 🔒 Güvenlik: Kim yaptı bilgisini asla client'tan alma, oturum'dan al
            $performedBy = auth()->user()->name;

            $this->authorize('adjust', $stock);

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

    public function useStock(UseStockRequest $request, $id): JsonResponse
    {
        try {
            $data  = $request->validated();
            $notes = $data['notes'] ?? '';
            if (!empty($data['used_by'])) {
                $notes .= "\nKullanan: " . $data['used_by'];
            }
            if (!empty($data['reason'])) {
                $notes .= "\nSebep: " . $data['reason'];
            }

            // 🔒 Güvenlik: Kim yaptı bilgisini asla client'tan alma, oturum'dan al
            $performedBy = auth()->user()->name;

            $stock = $this->stockService->getStockById((int)$id);
            if (!$stock) {
                throw new StockNotFoundException($id);
            }

            $this->authorize('use', $stock);

            $this->stockService->useStock(
                (int)$id,
                $data['quantity'],
                $performedBy,
                auth()->id(),
                $notes,
                $data['is_from_reserved'] ?? false
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
            $this->authorize('viewAny', Stock::class);
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
            $this->authorize('viewAny', Stock::class);
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
            $this->authorize('viewAny', Stock::class);
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
            $this->authorize('viewAny', Stock::class);
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

    public function deactivate(int $id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById($id);
            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('update', $stock);

            $updatedStock = $this->stockService->updateStock($id, [
                'is_active' => false,
                'status' => 'inactive'
            ]);

            return $this->success(new StockResource($updatedStock->load('product')), 'Stok pasif duruma getirildi');
        } catch (\Exception $e) {
            Log::error('Stock Deactivate Error: ' . $e->getMessage());
            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function reactivate(int $id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById($id);
            if (!$stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('update', $stock);

            $updatedStock = $this->stockService->updateStock($id, [
                'is_active' => true,
                'status' => 'active'
            ]);

            return $this->success(new StockResource($updatedStock->load('product')), 'Stok başarıyla aktif edildi');
        } catch (\Exception $e) {
            Log::error('Stock Reactivate Error: ' . $e->getMessage());
            return $this->error(__('messages.server_error'), 500);
        }
    }
}
