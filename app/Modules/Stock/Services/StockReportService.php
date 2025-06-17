<?php
// app/Modules/Stock/Services/StockReportService.php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\StockRepositoryInterface;
use App\Modules\Stock\Repositories\Interfaces\StockTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockReportService
{
    protected $stockRepository;
    protected $transactionRepository;

    public function __construct(
        StockRepositoryInterface $stockRepository,
        StockTransactionRepositoryInterface $transactionRepository
    ) {
        $this->stockRepository = $stockRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function getStockSummaryReport(int $clinicId = null): array
    {
        $query = $this->stockRepository->getBaseQuery();

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_items,
            SUM(current_stock) as total_quantity,
            SUM(current_stock * purchase_price) as total_value,
            SUM(CASE WHEN current_stock <= yellow_alert_level THEN 1 ELSE 0 END) as low_stock_items,
            SUM(CASE WHEN current_stock <= red_alert_level THEN 1 ELSE 0 END) as critical_stock_items,
            SUM(CASE WHEN track_expiry = 1 AND expiry_date < NOW() THEN 1 ELSE 0 END) as expired_items,
            SUM(CASE WHEN track_expiry = 1 AND expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon_items
        ')->first();

        return [
            'total_items' => $summary->total_items ?? 0,
            'total_quantity' => $summary->total_quantity ?? 0,
            'total_value' => round($summary->total_value ?? 0, 2),
            'low_stock_items' => $summary->low_stock_items ?? 0,
            'critical_stock_items' => $summary->critical_stock_items ?? 0,
            'expired_items' => $summary->expired_items ?? 0,
            'expiring_soon_items' => $summary->expiring_soon_items ?? 0
        ];
    }

    public function getStockMovementReport(Carbon $startDate, Carbon $endDate, int $clinicId = null): array
    {
        $query = $this->transactionRepository->getBaseQuery()
                    ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $movements = $query->selectRaw('
            type,
            COUNT(*) as transaction_count,
            SUM(quantity) as total_quantity,
            SUM(total_price) as total_value
        ')->groupBy('type')->get();

        return $movements->mapWithKeys(function ($movement) {
            return [$movement->type => [
                'count' => $movement->transaction_count,
                'quantity' => $movement->total_quantity,
                'value' => round($movement->total_value ?? 0, 2)
            ]];
        })->toArray();
    }

    public function getTopUsedItemsReport(Carbon $startDate, Carbon $endDate, int $limit = 10, int $clinicId = null): array
    {
        $query = DB::table('stock_transactions as st')
                    ->join('stocks as s', 'st.stock_id', '=', 's.id')
                    ->where('st.type', 'usage')
                    ->whereBetween('st.transaction_date', [$startDate, $endDate]);

        if ($clinicId) {
            $query->where('st.clinic_id', $clinicId);
        }

        return $query->selectRaw('
            s.name,
            s.unit,
            SUM(st.quantity) as total_used,
            COUNT(st.id) as usage_count,
            AVG(st.quantity) as avg_usage
        ')
        ->groupBy('s.id', 's.name', 's.unit')
        ->orderByDesc('total_used')
        ->limit($limit)
        ->get()
        ->toArray();
    }

    public function getSupplierPerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        return DB::table('suppliers as sup')
                  ->join('stocks as s', 'sup.id', '=', 's.supplier_id')
                  ->join('stock_transactions as st', 's.id', '=', 'st.stock_id')
                  ->where('st.type', 'purchase')
                  ->whereBetween('st.transaction_date', [$startDate, $endDate])
                  ->selectRaw('
                      sup.name as supplier_name,
                      COUNT(DISTINCT s.id) as products_count,
                      COUNT(st.id) as purchase_count,
                      SUM(st.quantity) as total_quantity,
                      SUM(st.total_price) as total_value,
                      AVG(st.unit_price) as avg_unit_price
                  ')
                  ->groupBy('sup.id', 'sup.name')
                  ->orderByDesc('total_value')
                  ->get()
                  ->toArray();
    }

    public function getExpiryReport(int $days = 30, int $clinicId = null): array
    {
        $query = $this->stockRepository->getBaseQuery()
                    ->where('track_expiry', true)
                    ->whereNotNull('expiry_date');

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        // Süresi geçen ürünler
        $expired = $query->clone()
                        ->where('expiry_date', '<', now())
                        ->where('current_stock', '>', 0)
                        ->selectRaw('
                            name,
                            current_stock,
                            expiry_date,
                            DATEDIFF(NOW(), expiry_date) as days_expired,
                            (current_stock * purchase_price) as lost_value
                        ')
                        ->orderBy('expiry_date')
                        ->get();

        // Süresi yaklaşan ürünler
        $expiringSoon = $query->clone()
                             ->whereBetween('expiry_date', [now(), now()->addDays($days)])
                             ->selectRaw('
                                 name,
                                 current_stock,
                                 expiry_date,
                                 DATEDIFF(expiry_date, NOW()) as days_to_expiry,
                                 (current_stock * purchase_price) as value_at_risk
                             ')
                             ->orderBy('expiry_date')
                             ->get();

        return [
            'expired' => $expired->toArray(),
            'expiring_soon' => $expiringSoon->toArray(),
            'total_expired_value' => $expired->sum('lost_value'),
            'total_at_risk_value' => $expiringSoon->sum('value_at_risk')
        ];
    }

    public function getClinicComparisonReport(): array
    {
        return DB::table('clinics as c')
                  ->leftJoin('stocks as s', 'c.id', '=', 's.clinic_id')
                  ->selectRaw('
                      c.name as clinic_name,
                      c.code as clinic_code,
                      COUNT(s.id) as total_items,
                      SUM(s.current_stock) as total_quantity,
                      SUM(s.current_stock * s.purchase_price) as total_value,
                      SUM(CASE WHEN s.current_stock <= s.yellow_alert_level THEN 1 ELSE 0 END) as low_stock_count,
                      SUM(CASE WHEN s.current_stock <= s.red_alert_level THEN 1 ELSE 0 END) as critical_stock_count
                  ')
                  ->where('c.is_active', true)
                  ->groupBy('c.id', 'c.name', 'c.code')
                  ->orderBy('c.name')
                  ->get()
                  ->toArray();
    }

    public function getCustomReport(array $filters): array
    {
        $query = DB::table('stocks as s')
                   ->join('clinics as c', 's.clinic_id', '=', 'c.id')
                   ->join('suppliers as sup', 's.supplier_id', '=', 'sup.id')
                   ->select([
                       's.name as stock_name',
                       's.code as stock_code',
                       'c.name as clinic_name',
                       'sup.name as supplier_name',
                       's.current_stock',
                       's.unit',
                       's.purchase_price',
                       's.expiry_date',
                       's.status',
                       DB::raw('(s.current_stock * s.purchase_price) as total_value'),
                       DB::raw('CASE
                           WHEN s.current_stock <= s.red_alert_level THEN "critical"
                           WHEN s.current_stock <= s.yellow_alert_level THEN "low"
                           ELSE "normal"
                       END as stock_status')
                   ]);

        // Filtreleri uygula
        if (!empty($filters['clinic_id'])) {
            $query->where('s.clinic_id', $filters['clinic_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('s.supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['category'])) {
            $query->where('s.category', $filters['category']);
        }

        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'low':
                    $query->whereRaw('s.current_stock <= s.yellow_alert_level');
                    break;
                case 'critical':
                    $query->whereRaw('s.current_stock <= s.red_alert_level');
                    break;
                case 'expired':
                    $query->where('s.track_expiry', true)
                          ->where('s.expiry_date', '<', now());
                    break;
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('s.name', 'like', $search)
                  ->orWhere('s.code', 'like', $search)
                  ->orWhere('sup.name', 'like', $search);
            });
        }

        return $query->orderBy('s.name')->get()->toArray();
    }
}