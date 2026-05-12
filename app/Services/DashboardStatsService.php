<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    public function getStatsForUser(User $user): array
    {
        $clinicId = $user->clinic_id;
        $version = \App\Support\StockStatsCache::version();
        $cacheKey = "dashboard_stats_v3_{$version}_".($clinicId ?? 'global');

        return Cache::remember($cacheKey, 120, fn () => $this->buildStats($clinicId));
    }

    public function invalidate(): void
    {
        // For simplicity, flush all dashboard stats or use a tag if supported
        // Here we just use a versioned key approach or simple clear if known
        Cache::forget('dashboard_stats_v3_global');
        // We might not know all clinic IDs here, so a better approach would be versioning
    }

    private function buildStats(?int $clinicId = null): array
    {
        $stockStats = $this->stockService->getStockStats($clinicId);

        return [
            'system_name' => config('app.name', 'Denti'),
            'total_users' => User::query()->count(),
            'total_doctors' => (int) DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', User::class)
                ->where('roles.name', 'Doctor')
                ->distinct('model_has_roles.model_id')
                ->count('model_has_roles.model_id'),
            'total_employees' => User::query()->count(),
            'total_clinics' => Clinic::query()->count(),
            'total_suppliers' => Supplier::query()->count(),
            'is_super_admin' => false,

            // Stock specific stats
            'total_stock_items' => $stockStats['total_items'],
            'low_stock_items' => $stockStats['low_stock_items'],
            'critical_stock_items' => $stockStats['critical_stock_items'],
            'low_expiring_items' => $stockStats['low_expiring_items'],
            'critical_expiring_items' => $stockStats['critical_expiring_items'],
            'total_stock_value' => $stockStats['total_value'],
        ];
    }
}
