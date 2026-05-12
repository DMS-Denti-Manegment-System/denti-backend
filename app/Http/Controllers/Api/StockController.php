<?php

// app/Modules/Stock/Controllers/StockController.php

namespace App\Http\Controllers\Api;

use App\Enums\StockStatus;
use App\Exceptions\Stock\InsufficientStockException;
use App\Exceptions\Stock\StockNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\StoreStockRequest;
use App\Http\Requests\UpdateStockRequest;
use App\Http\Requests\UseStockRequest;
use App\Http\Resources\StockResource;
use App\Http\Resources\StockTransactionResource;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
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
                'stock_status', 'search', 'expiry_filter', 'name',
            ]);

            // 🛡️ Güvenlik: Sayfalama limitine üst sınır koy (DDoS koruması)
            $perPage = min((int) $request->query('per_page', 50), 100);

            $this->authorize('viewAny', Stock::class);

            $stocks = $this->stockService->getAllStocks($filters, $perPage);

            return $this->success(StockResource::collection($stocks));
        } catch (\Exception $e) {
            Log::error('Stock Index Error: '.$e->getMessage(), ['user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int) $id);

            if (! $stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('view', $stock);

            return $this->success(new StockResource($stock));
        } catch (\Exception $e) {
            Log::error('Stock Show Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function transactions($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int) $id);
            if (! $stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('view', $stock);

            $transactions = $this->stockService->getStockTransactions((int) $id);

            return $this->success(StockTransactionResource::collection($transactions));
        } catch (\Exception $e) {
            Log::error('Stock Transactions Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function store(StoreStockRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Defaults (moved business logic preference to service but keep these simple defaults if needed)
            $data['currency'] = $data['currency'] ?? 'TRY';
            $data['track_expiry'] = $data['track_expiry'] ?? true;

            $this->authorize('create', Stock::class);

            $stock = $this->stockService->createStock($data);

            $stock->load('product');

            return $this->success(new StockResource($stock), 'Stok başarıyla oluşturuldu', 201);
        } catch (\Exception $e) {
            Log::error('Stock Store Error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function update(UpdateStockRequest $request, $id): JsonResponse
    {
        try {
            $data = $request->validated();

            if (isset($data['is_active'])) {
                $data['status'] = $data['is_active'] ? StockStatus::ACTIVE : StockStatus::INACTIVE;
            }

            $stock = $this->stockService->getStockById((int) $id);
            if (! $stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('update', $stock);

            $stock = $this->stockService->updateStock((int) $id, $data);

            return $this->success(new StockResource($stock), 'Stok başarıyla güncellendi');
        } catch (\Exception $e) {
            Log::error('Stock Update Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById((int) $id);
            if (! $stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('delete', $stock);

            $this->stockService->deleteStock((int) $id);

            return $this->success(null, 'Stok başarıyla silindi');
        } catch (\Exception $e) {
            Log::error('Stock Destroy Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function adjustStock(AdjustStockRequest $request, $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $isSubUnit = $data['is_sub_unit'] ?? false;

            $stock = $this->stockService->getStockById((int) $id);
            if (! $stock) {
                throw new StockNotFoundException($id);
            }

            if ($data['operation_type'] === 'sync') {
                $targetQuantity = $data['quantity'];
                $adjustmentType = 'sync';
                $delta = 0; // Service will calculate
            } else {
                $targetQuantity = 0;
                $adjustmentType = $data['operation_type'];
                $delta = $data['quantity'];
            }

            $performedBy = auth()->user()->name;

            $this->authorize('adjust', $stock);

            $this->stockService->adjustStock(
                (int) $id,
                $delta,
                $data['reason'],
                $performedBy,
                $isSubUnit,
                $adjustmentType,
                $targetQuantity
            );

            return $this->success(new StockResource($this->stockService->getStockById((int) $id)), 'Stok başarıyla düzeltildi');
        } catch (StockNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (InsufficientStockException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Stock Adjust Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function useStock(UseStockRequest $request, $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $notes = $data['notes'] ?? '';
            if (! empty($data['used_by'])) {
                $notes .= "\nKullanan: ".$data['used_by'];
            }
            if (! empty($data['reason'])) {
                $notes .= "\nSebep: ".$data['reason'];
            }

            $performedBy = auth()->user()->name;

            $stock = $this->stockService->getStockById((int) $id);
            if (! $stock) {
                throw new StockNotFoundException($id);
            }

            $this->authorize('use', $stock);

            $this->stockService->useStock(
                (int) $id,
                $data['quantity'],
                $performedBy,
                auth()->id(),
                $notes,
                $data['is_from_reserved'] ?? false,
                (bool) ($data['is_sub_unit'] ?? false),
                array_key_exists('show_zero_stock_in_critical', $data) ? (bool) $data['show_zero_stock_in_critical'] : null
            );

            return $this->success(new StockResource($this->stockService->getStockById((int) $id)), 'Stok kullanımı kaydedildi');
        } catch (StockNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (InsufficientStockException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Stock Use Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getLowLevel(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Stock::class);
            $clinicId = $request->query('clinic_id');
            $cacheKey = sprintf('stocks.low_level.%s', $clinicId ?? 'all');
            $items = Cache::remember($cacheKey, 60, fn () => $this->stockService->getLowStockItems($clinicId ? (int) $clinicId : null));

            return $this->success(StockResource::collection($items));
        } catch (\Exception $e) {
            Log::error('Stock LowLevel Error: '.$e->getMessage(), ['user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getCriticalLevel(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Stock::class);
            $clinicId = $request->query('clinic_id');
            $cacheKey = sprintf('stocks.critical_level.%s', $clinicId ?? 'all');
            $items = Cache::remember($cacheKey, 60, fn () => $this->stockService->getCriticalStockItems($clinicId ? (int) $clinicId : null));

            return $this->success(StockResource::collection($items));
        } catch (\Exception $e) {
            Log::error('Stock CriticalLevel Error: '.$e->getMessage(), ['user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getExpiring(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Stock::class);
            $days = $request->query('days', 30);
            $clinicId = $request->query('clinic_id');
            $cacheKey = sprintf('stocks.expiring.%s.%d', $clinicId ?? 'all', (int) $days);
            $items = Cache::remember($cacheKey, 60, fn () => $this->stockService->getExpiringItems((int) $days, $clinicId ? (int) $clinicId : null));

            return $this->success(StockResource::collection($items));
        } catch (\Exception $e) {
            Log::error('Stock Expiring Error: '.$e->getMessage(), ['user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function getStats(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Stock::class);
            $clinicId = $request->query('clinic_id');

            $stats = $this->stockService->getStockStats($clinicId ? (int) $clinicId : null);

            return $this->success($stats);
        } catch (\Exception $e) {
            Log::error('Stock Stats Error: '.$e->getMessage(), ['user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function forceDelete($id): JsonResponse
    {
        try {
            $deleted = $this->stockService->forceDeleteStock((int) $id);

            if (! $deleted) {
                return $this->error('Stok bulunamadı', 404);
            }

            return $this->success(null, 'Stok kalıcı olarak silindi');
        } catch (\Exception $e) {
            Log::error('Stock ForceDelete Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function deactivate(int $id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById($id);
            if (! $stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('update', $stock);

            $updatedStock = $this->stockService->updateStock($id, [
                'is_active' => false,
                'status' => StockStatus::INACTIVE,
            ]);

            return $this->success(new StockResource($updatedStock->load('product')), 'Stok pasif duruma getirildi');
        } catch (\Exception $e) {
            Log::error('Stock Deactivate Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    public function reactivate(int $id): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById($id);
            if (! $stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('update', $stock);

            $updatedStock = $this->stockService->updateStock($id, [
                'is_active' => true,
                'status' => StockStatus::ACTIVE,
            ]);

            return $this->success(new StockResource($updatedStock->load('product')), 'Stok başarıyla aktif edildi');
        } catch (\Exception $e) {
            Log::error('Stock Reactivate Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }

    /**
     * Get stock transaction history
     */
    public function getTransactions(int $id, Request $request): JsonResponse
    {
        try {
            $stock = $this->stockService->getStockById($id);
            if (! $stock) {
                return $this->error('Stok bulunamadı', 404);
            }

            $this->authorize('view', $stock);

            $filters = $request->only(['type', 'date_from', 'date_to', 'per_page']);
            $transactions = $this->stockService->getStockTransactions($id, $filters);

            return $this->success($transactions, 'İşlem geçmişi başarıyla getirildi');
        } catch (\Exception $e) {
            Log::error('Stock Transactions Error: '.$e->getMessage(), ['id' => $id, 'user_id' => auth()->id()]);

            return $this->error(__('messages.server_error'), 500);
        }
    }
}
