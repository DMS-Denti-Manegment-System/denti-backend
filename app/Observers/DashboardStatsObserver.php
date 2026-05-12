<?php

namespace App\Observers;

use App\Services\DashboardStatsService;
use App\Support\StockStatsCache;
use Illuminate\Database\Eloquent\Model;

class DashboardStatsObserver
{
    public function __construct(
        private readonly DashboardStatsService $dashboardStatsService
    ) {}

    public function created(Model $model): void
    {
        $this->invalidate($model);
    }

    public function updated(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    public function restored(Model $model): void
    {
        $this->invalidate($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $this->dashboardStatsService->invalidate();
        StockStatsCache::bump();
    }
}
