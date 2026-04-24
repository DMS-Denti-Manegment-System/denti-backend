<?php
// app/Modules/Stock/Controllers/StockReportController.php

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\StockReportService;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StockReportController extends Controller
{
    use JsonResponseTrait;

    protected $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    public function summary(Request $request): JsonResponse
    {
        try {
            $request->validate(['clinic_id' => 'nullable|integer|exists:clinics,id']);
            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;
            
            $summary = $this->stockReportService->getStockSummaryReport($companyId, $clinicId ? (int)$clinicId : null);

            return $this->success($summary);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Rapor oluşturulurken bir hata oluştu.', 500);
        }
    }

    public function movements(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'clinic_id' => 'nullable|integer|exists:clinics,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date'
            ]);

            $startDate = $request->query('start_date')
                ? Carbon::parse($request->query('start_date'))
                : now()->subMonth();

            $endDate = $request->query('end_date')
                ? Carbon::parse($request->query('end_date'))
                : now();

            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;

            $movements = $this->stockReportService->getStockMovementReport(
                $companyId,
                $startDate, 
                $endDate, 
                $clinicId ? (int)$clinicId : null
            );

            return $this->success($movements, 'Hareket raporu', 200, [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'clinic_id' => $clinicId
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Hareket raporu oluşturulurken bir hata oluştu.', 500);
        }
    }

    public function topUsedItems(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'clinic_id' => 'nullable|integer|exists:clinics,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $startDate = $request->query('start_date')
                ? Carbon::parse($request->query('start_date'))
                : now()->subMonth();

            $endDate = $request->query('end_date')
                ? Carbon::parse($request->query('end_date'))
                : now();

            $limit = (int) $request->query('limit', 10);
            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;

            $items = $this->stockReportService->getTopUsedItemsReport(
                $companyId,
                $startDate, 
                $endDate, 
                $limit, 
                $clinicId ? (int)$clinicId : null
            );

            return $this->success($items, 'En çok kullanılan ürünler', 200, [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'limit' => $limit,
                'clinic_id' => $clinicId
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Kullanım raporu oluşturulurken bir hata oluştu.', 500);
        }
    }


    public function expiryReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'clinic_id' => 'nullable|integer|exists:clinics,id',
                'days' => 'nullable|integer|min:1'
            ]);

            $days = (int) $request->query('days', 30);
            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;

            $report = $this->stockReportService->getExpiryReport($companyId, $days, $clinicId ? (int)$clinicId : null);

            return $this->success($report, 'Süre dolum raporu', 200, [
                'days' => $days,
                'clinic_id' => $clinicId
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Süre dolum raporu oluşturulurken bir hata oluştu.', 500);
        }
    }

    public function clinicComparison(): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            $comparison = $this->stockReportService->getClinicComparisonReport($companyId);

            return $this->success($comparison);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Klinik karşılaştırma raporu oluşturulurken bir hata oluştu.', 500);
        }
    }

    public function trends(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'clinic_id' => 'nullable|integer|exists:clinics,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'period' => 'nullable|string|in:day,month'
            ]);

            $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : now()->subDays(30);
            $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date')) : now();
            $period = $request->query('period', 'day');
            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;

            $trends = $this->stockReportService->getConsumptionTrend($companyId, $startDate, $endDate, $period, $clinicId ? (int)$clinicId : null);

            return $this->success($trends);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Trend raporu oluşturulurken bir hata oluştu.', 500);
        }
    }


    public function categories(Request $request): JsonResponse
    {
        try {
            $request->validate(['clinic_id' => 'nullable|integer|exists:clinics,id']);
            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;
            
            $distribution = $this->stockReportService->getCategoryDistribution($companyId, $clinicId ? (int)$clinicId : null);

            return $this->success($distribution);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Kategori raporu oluşturulurken bir hata oluştu.', 500);
        }
    }

    public function forecast(Request $request): JsonResponse
    {
        try {
            $request->validate(['clinic_id' => 'nullable|integer|exists:clinics,id']);
            $clinicId = $request->query('clinic_id');
            $companyId = auth()->user()->company_id;
            
            $forecast = $this->stockReportService->getLowStockForecast($companyId, $clinicId ? (int)$clinicId : null);

            return $this->success($forecast);
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('Tahminleme raporu oluşturulurken bir hata oluştu.', 500);
        }
    }
}
