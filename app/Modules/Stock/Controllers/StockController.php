<?php
// ==============================================
// 1. StockController
// app/Modules/Stock/Controllers/StockController.php
// ==============================================

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'clinic_id', 'supplier_id', 'category', 'status',
            'stock_status', 'search', 'expiry_filter'
        ]);

        $stocks = $this->stockService->getAllStocks($filters);

        return response()->json([
            'success' => true,
            'data' => $stocks
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:stocks,code',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'supplier_id' => 'required|exists:suppliers,id',
            'clinic_id' => 'required|exists:clinics,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:today',
            'current_stock' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'critical_stock_level' => 'required|integer|min:0',
            'yellow_alert_level' => 'required|integer|min:0',
            'red_alert_level' => 'required|integer|min:0',
            'track_expiry' => 'boolean',
            'track_batch' => 'boolean',
            'storage_location' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stock = $this->stockService->createStock($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Stok başarıyla oluşturuldu',
                'data' => $stock
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        $stock = $this->stockService->getStockById($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stok bulunamadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $stock
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'sometimes|required|string|max:50',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'min_stock_level' => 'sometimes|required|integer|min:0',
            'critical_stock_level' => 'sometimes|required|integer|min:0',
            'yellow_alert_level' => 'sometimes|required|integer|min:0',
            'red_alert_level' => 'sometimes|required|integer|min:0',
            'track_expiry' => 'boolean',
            'track_batch' => 'boolean',
            'storage_location' => 'nullable|string|max:255',
            'status' => 'in:active,inactive,discontinued'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stock = $this->stockService->updateStock($id, $validator->validated());

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stok başarıyla güncellendi',
                'data' => $stock
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = $this->stockService->deleteStock($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stok başarıyla silindi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function adjustStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:500',
            'performed_by' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->stockService->adjustStock(
                $id,
                $validator->validated()['quantity'],
                $validator->validated()['reason'],
                $validator->validated()['performed_by']
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok düzeltmesi başarısız'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stok başarıyla düzeltildi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function useStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'performed_by' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->stockService->useStock(
                $id,
                $validator->validated()['quantity'],
                $validator->validated()['performed_by'],
                $validator->validated()['notes'] ?? null
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yetersiz stok veya stok bulunamadı'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stok kullanımı kaydedildi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getLowStockItems(Request $request)
    {
        $clinicId = $request->query('clinic_id');
        $items = $this->stockService->getLowStockItems($clinicId);

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    public function getCriticalStockItems(Request $request)
    {
        $clinicId = $request->query('clinic_id');
        $items = $this->stockService->getCriticalStockItems($clinicId);

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    public function getExpiringItems(Request $request)
    {
        $days = $request->query('days', 30);
        $clinicId = $request->query('clinic_id');
        $items = $this->stockService->getExpiringItems($days, $clinicId);

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
}