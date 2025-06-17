<?php
// ==============================================
// 3. StockReportController.php - DÜZELTİLMİŞ
// app/Modules/Stock/Controllers/StockReportController.php
// ==============================================

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StockReportController extends Controller
{
    protected $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    public function summary(Request $request)
    {
        try {
            $clinicId = $request->query('clinic_id');
            $summary = $this->stockReportService->getStockSummaryReport($clinicId);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function movements(Request $request)
    {
        try {
            $startDate = $request->query('start_date')
                ? Carbon::parse($request->query('start_date'))
                : now()->subMonth();

            $endDate = $request->query('end_date')
                ? Carbon::parse($request->query('end_date'))
                : now();

            $clinicId = $request->query('clinic_id');

            $movements = $this->stockReportService->getStockMovementReport($startDate, $endDate, $clinicId);

            return response()->json([
                'success' => true,
                'data' => $movements,
                'meta' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'clinic_id' => $clinicId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function topUsedItems(Request $request)
    {
        try {
            $startDate = $request->query('start_date')
                ? Carbon::parse($request->query('start_date'))
                : now()->subMonth();

            $endDate = $request->query('end_date')
                ? Carbon::parse($request->query('end_date'))
                : now();

            $limit = (int) $request->query('limit', 10);
            $clinicId = $request->query('clinic_id');

            $items = $this->stockReportService->getTopUsedItemsReport($startDate, $endDate, $limit, $clinicId);

            return response()->json([
                'success' => true,
                'data' => $items,
                'meta' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'limit' => $limit,
                    'clinic_id' => $clinicId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function supplierPerformance(Request $request)
    {
        try {
            $startDate = $request->query('start_date')
                ? Carbon::parse($request->query('start_date'))
                : now()->subMonth();

            $endDate = $request->query('end_date')
                ? Carbon::parse($request->query('end_date'))
                : now();

            $performance = $this->stockReportService->getSupplierPerformanceReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $performance,
                'meta' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function expiryReport(Request $request)
    {
        try {
            $days = (int) $request->query('days', 30);
            $clinicId = $request->query('clinic_id');

            $report = $this->stockReportService->getExpiryReport($days, $clinicId);

            return response()->json([
                'success' => true,
                'data' => $report,
                'meta' => [
                    'days' => $days,
                    'clinic_id' => $clinicId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clinicComparison()
    {
        try {
            $comparison = $this->stockReportService->getClinicComparisonReport();

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function customReport(Request $request)
    {
        try {
            $filters = $request->only([
                'clinic_id', 'supplier_id', 'category', 'stock_status', 'search'
            ]);

            // Boş filtreleri temizle
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $report = $this->stockReportService->getCustomReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'meta' => [
                    'filters' => $filters,
                    'total_records' => count($report)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}