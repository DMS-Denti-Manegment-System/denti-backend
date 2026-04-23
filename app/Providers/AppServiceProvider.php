<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Events\Stock\StockLevelChanged;
use App\Listeners\Stock\CheckStockAlertsListener;
use App\Listeners\Stock\ClearStockCacheListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Stok seviyesi değiştiğinde tetiklenecek listener'lar
        Event::listen(StockLevelChanged::class, CheckStockAlertsListener::class);
        Event::listen(StockLevelChanged::class, ClearStockCacheListener::class);
    }
}

