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
        $companyId = auth()->user()->company_id;
        $thirtyDaysFromNow = now()->addDays(30)->format('Y-m-d');
        $today = now()->format('Y-m-d');

        $query = $this->stockRepository->getBaseQuery()
                    ->where('company_id', $companyId);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_items,
            SUM(current_stock) as total_main_quantity,
            SUM(CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) as total_base_quantity,
            SUM(current_stock * purchase_price) as total_value,
            SUM(CASE 
                WHEN (CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) <= yellow_alert_level THEN 1 
                ELSE 0 
            END) as low_stock_items,
            SUM(CASE 
                WHEN (CASE WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock ELSE current_stock END) <= red_alert_level THEN 1 
                ELSE 0 
            END) as critical_stock_items,
            SUM(CASE WHEN track_expiry = 1 AND expiry_date < ? THEN 1 ELSE 0 END) as expired_items,
            SUM(CASE WHEN track_expiry = 1 AND expiry_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_soon_items,
            SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock_items
        ', [$today, $today, $thirtyDaysFromNow])->first();

        return [
            'total_items' => (int) ($summary->total_items ?? 0),
            'total_main_quantity' => (int) ($summary->total_main_quantity ?? 0),
            'total_base_quantity' => (int) ($summary->total_base_quantity ?? 0),
            'total_value' => round((float) ($summary->total_value ?? 0), 2),
            'low_stock_items' => (int) ($summary->low_stock_items ?? 0),
            'critical_stock_items' => (int) ($summary->critical_stock_items ?? 0),
            'out_of_stock_items' => (int) ($summary->out_of_stock_items ?? 0),
            'expired_items' => (int) ($summary->expired_items ?? 0),
            'expiring_soon_items' => (int) ($summary->expiring_soon_items ?? 0)
        ];
    }

    public function getStockMovementReport(Carbon $startDate, Carbon $endDate, int $clinicId = null): array
    {
        $query = $this->transactionRepository->getBaseQuery()
                    ->where('company_id', auth()->user()->company_id)
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

        $result = [];
        $types = ['purchase', 'usage', 'adjustment', 'transfer_in', 'transfer_out', 'return'];
        
        foreach ($types as $type) {
            $movement = $movements->where('type', $type)->first();
            $result[$type] = [
                'count' => (int) ($movement->transaction_count ?? 0),
                'quantity' => (int) ($movement->total_quantity ?? 0),
                'value' => round((float) ($movement->total_value ?? 0), 2)
            ];
        }

        return $result;
    }

    public function getTopUsedItemsReport(Carbon $startDate, Carbon $endDate, int $limit = 10, int $clinicId = null): array
    {
        $query = DB::table('stock_transactions as st')
                    ->join('stocks as s', 'st.stock_id', '=', 's.id')
                    ->whereNull('s.deleted_at')
                    ->where('st.type', 'usage')
                    ->where('st.company_id', auth()->user()->company_id)
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
        ->map(function ($item) {
            $item->total_used = (int) $item->total_used;
            $item->usage_count = (int) $item->usage_count;
            $item->avg_usage = round((float) $item->avg_usage, 2);
            return $item;
        })
        ->toArray();
    }


    public function getExpiryReport(int $days = 30, int $clinicId = null): array
    {
        $companyId = auth()->user()->company_id;
        $query = $this->stockRepository->getBaseQuery()
                    ->where('company_id', $companyId)
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
                            (current_stock * purchase_price) as lost_value
                        ')
                        ->orderBy('expiry_date')
                        ->get()
                        ->map(function($item) {
                            $item->days_expired = now()->diffInDays(Carbon::parse($item->expiry_date));
                            return $item;
                        });

        // Süresi yaklaşan ürünler
        $expiringSoon = $query->clone()
                             ->whereBetween('expiry_date', [now(), now()->addDays($days)])
                             ->selectRaw('
                                 name,
                                 current_stock,
                                 expiry_date,
                                 (current_stock * purchase_price) as value_at_risk
                             ')
                             ->orderBy('expiry_date')
                             ->get()
                             ->map(function($item) {
                                 $item->days_to_expiry = now()->diffInDays(Carbon::parse($item->expiry_date));
                                 return $item;
                             });

        return [
            'expired' => $expired->toArray(),
            'expiring_soon' => $expiringSoon->toArray(),
            'total_expired_value' => round((float) $expired->sum('lost_value'), 2),
            'total_at_risk_value' => round((float) $expiringSoon->sum('value_at_risk'), 2)
        ];
    }

    public function getClinicComparisonReport(): array
    {
        return DB::table('clinics as c')
                  ->leftJoin('stocks as s', function($join) {
                      $join->on('c.id', '=', 's.clinic_id')
                          ->whereNull('s.deleted_at');
                  })
                  ->selectRaw('
                      c.name as clinic_name,
                      c.code as clinic_code,
                      COUNT(s.id) as total_items,
                      SUM(s.current_stock) as total_quantity,
                      SUM(s.current_stock * s.purchase_price) as total_value,
                      SUM(CASE WHEN s.current_stock <= yellow_alert_level THEN 1 ELSE 0 END) as low_stock_count,
                      SUM(CASE WHEN s.current_stock <= red_alert_level THEN 1 ELSE 0 END) as critical_stock_count
                  ')
                  ->where('c.company_id', auth()->user()->company_id)
                  ->whereNull('c.deleted_at')
                  ->where('c.is_active', true)
                  ->groupBy('c.id', 'c.name', 'c.code')
                  ->orderBy('c.name')
                  ->get()
                  ->map(function ($item) {
                      $item->total_items = (int) $item->total_items;
                      $item->total_quantity = (int) $item->total_quantity;
                      $item->total_value = round((float) $item->total_value, 2);
                      $item->low_stock_count = (int) $item->low_stock_count;
                      $item->critical_stock_count = (int) $item->critical_stock_count;
                      return $item;
                  })
                  ->toArray();
    }


    public function getConsumptionTrend(Carbon $startDate, Carbon $endDate, string $period = 'day', int $clinicId = null): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        
        if ($isSqlite) {
            $groupBy = $period === 'month' ? "strftime('%Y-%m', transaction_date)" : "strftime('%Y-%m-%d', transaction_date)";
        } else {
            $groupBy = $period === 'month' ? "DATE_FORMAT(transaction_date, '%Y-%m')" : "DATE_FORMAT(transaction_date, '%Y-%m-%d')";
        }
        
        $query = DB::table('stock_transactions')
            ->where('company_id', auth()->user()->company_id)
            ->where('type', 'usage')
            ->whereNull('deleted_at')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->selectRaw("
                        $groupBy as period,
                        COUNT(*) as transaction_count,
                        SUM(quantity) as total_quantity
                    ")
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get()
                    ->toArray();
    }


    public function getCategoryDistribution(int $clinicId = null): array
    {
        $query = DB::table('stocks')
                    ->where('company_id', auth()->user()->company_id)
                    ->whereNull('deleted_at')
                    ->selectRaw('
                        category,
                        COUNT(*) as item_count,
                        SUM(current_stock * purchase_price) as total_value
                    ')
                    ->groupBy('category');

        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->get()->toArray();
    }

    public function getLowStockForecast(int $clinicId = null): array
    {
        // Son 30 günlük ortalama günlük tüketimi hesapla
        $usageData = DB::table('stock_transactions')
                        ->where('company_id', auth()->user()->company_id)
                        ->where('type', 'usage')
                        ->where('transaction_date', '>=', now()->subDays(30))
                        ->selectRaw('stock_id, SUM(quantity) / 30 as avg_daily_usage')
                        ->groupBy('stock_id');

        $query = DB::table('stocks as s')
                    ->leftJoinSub($usageData, 'usage', function ($join) {
                        $join->on('s.id', '=', 'usage.stock_id');
                    })
                    ->whereNull('s.deleted_at')
                    ->selectRaw('
                        s.name,
                        s.current_stock,
                        s.has_sub_unit,
                        s.current_sub_stock,
                        s.sub_unit_multiplier,
                        COALESCE(usage.avg_daily_usage, 0) as avg_daily_usage,
                        CASE 
                            WHEN COALESCE(usage.avg_daily_usage, 0) > 0 THEN 
                                (CASE WHEN s.has_sub_unit = 1 THEN (s.current_stock * COALESCE(s.sub_unit_multiplier, 1)) + s.current_sub_stock ELSE s.current_stock END) / usage.avg_daily_usage
                            ELSE 999 
                        END as estimated_days_left
                    ')
                    ->where('s.company_id', auth()->user()->company_id)
                    ->where('s.is_active', true)
                    ->where('s.current_stock', '>', 0);

        if ($clinicId) {
            $query->where('s.clinic_id', $clinicId);
        }

        return $query->orderBy('estimated_days_left')
                     ->limit(20)
                     ->get()
                     ->toArray();
    }
}
