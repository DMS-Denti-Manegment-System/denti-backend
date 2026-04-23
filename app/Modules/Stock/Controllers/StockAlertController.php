<?php

// ==============================================
// 6. StockAlertController
// app/Modules/Stock/Controllers/StockAlertController.php
// ==============================================

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockAlertController extends Controller
{
    protected $stockAlertService;

    public function __construct(StockAlertService $stockAlertService)
    {
        $this->stockAlertService = $stockAlertService;
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'clinic_id', 'type', 'severity', 'search', 'date_from', 'date_to'
        ]);
        
        $activeOnly = $request->query('active_only', true);

        // Otomatik Senkronizasyon: Her istekte güncel veriyi sağla
        $this->stockAlertService->syncAlerts($filters['clinic_id'] ?? null);

        if ($activeOnly === 'false' || $activeOnly === false) {
            $alerts = $this->stockAlertService->getAlerts($filters);
        } else {
            $alerts = $this->stockAlertService->getActiveAlerts($filters);
        }

        return response()->json([
            'success' => true,
            'data' => $alerts
        ]);
    }

    public function sync(Request $request)
    {
        $clinicId = $request->query('clinic_id');
        $count = $this->stockAlertService->syncAlerts($clinicId);

        return response()->json([
            'success' => true,
            'message' => "{$count} ürün tarandı ve uyarılar kontrol edildi.",
            'data' => ['processed_count' => $count]
        ]);
    }

    public function show($id)
    {
        $alert = $this->stockAlertService->getAlertById($id);

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alarm bulunamadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $alert
        ]);
    }

    public function resolve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'resolved_by' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->stockAlertService->resolveAlert(
                $id,
                $validator->validated()['resolved_by']
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alarm çözümlenemedi'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Alarm başarıyla çözümlendi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getStatistics(Request $request)
    {
        $clinicId = $request->query('clinic_id');
        
        // Otomatik Senkronizasyon
        $this->stockAlertService->syncAlerts($clinicId);
        
        $statistics = $this->stockAlertService->getAlertStatistics($clinicId);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    public function getPendingCount(Request $request)
    {
        $clinicId = $request->query('clinic_id');
        
        $query = \App\Modules\Stock\Models\Stock::query()
            ->where('is_active', true);
            
        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $today = now()->format('Y-m-d');
        
        // Dashboard ile Birebir Aynı Mantık (Disjoint Sets)
        $stats = $query->selectRaw("
            SUM(CASE 
                WHEN (CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) <= COALESCE(red_alert_level, critical_stock_level, 0) THEN 1 
                ELSE 0 
            END) as critical_items,
            SUM(CASE 
                WHEN (CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) > COALESCE(red_alert_level, critical_stock_level, 0) 
                AND (CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) <= COALESCE(yellow_alert_level, min_stock_level, 0) THEN 1 
                ELSE 0 
            END) as low_items,
            SUM(CASE WHEN track_expiry = 1 AND expiry_date < ? THEN 1 ELSE 0 END) as expired_items
        ", [$today])->first();

        // Toplam = Kritik + Düşük (Kritik olmayanlar) + Süresi Geçmiş
        // Not: Dashboard "17" diyorsa (8 Kritik + 9 Düşük), biz de 17 döndürmeliyiz.
        $total = (int)($stats->critical_items ?? 0) + (int)($stats->low_items ?? 0);

        return response()->json([
            'success' => true,
            'data' => ['count' => $total]
        ]);
    }

    public function getActive(Request $request)
    {
        $filters = $request->only([
            'clinic_id', 'type', 'severity', 'search', 'date_from', 'date_to'
        ]);
        
        $alerts = $this->stockAlertService->getActiveAlerts($filters);

        return response()->json([
            'success' => true,
            'data' => $alerts
        ]);
    }

    public function getSettings(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'email_notifications' => true,
                'push_notifications' => true,
                'daily_digest' => false,
            ]
        ]);
    }

    public function updateSettings(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Ayarlar güncellendi',
            'data' => $request->all()
        ]);
    }

    public function bulkResolve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'resolved_by' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = $this->stockAlertService->bulkResolve(
                $request->ids,
                $request->resolved_by
            );

            return response()->json([
                'success' => true,
                'message' => "{$count} alarm başarıyla çözümlendi",
                'data' => ['count' => $count]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function bulkDismiss(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = $this->stockAlertService->bulkDismiss($request->ids);

            return response()->json([
                'success' => true,
                'message' => "{$count} alarm yoksayıldı",
                'data' => ['count' => $count]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = $this->stockAlertService->bulkDelete($request->ids);

            return response()->json([
                'success' => true,
                'message' => "{$count} alarm silindi",
                'data' => ['count' => $count]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function dismiss(Request $request, $id)
    {
        try {
            $result = $this->stockAlertService->dismissAlert($id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alarm yoksayılamadı'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Alarm başarıyla yoksayıldı'
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
            $result = $this->stockAlertService->deleteAlert($id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alarm silinemedi'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Alarm başarıyla silindi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}