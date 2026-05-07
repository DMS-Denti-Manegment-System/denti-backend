<?php

namespace App\Providers;

use App\Events\Stock\StockLevelChanged;
use App\Listeners\Stock\CheckStockAlertsListener;
use App\Listeners\Stock\ClearStockCacheListener;
use App\Models\StockAlert;
use App\Models\StockTransaction;
use App\Observers\StockAlertObserver;
use App\Observers\StockTransactionObserver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Category Repository
        $this->app->bind(
            \App\Repositories\Interfaces\CategoryRepositoryInterface::class,
            \App\Repositories\CategoryRepository::class
        );

        // Todo Repository
        $this->app->bind(
            \App\Repositories\Interfaces\TodoRepositoryInterface::class,
            \App\Repositories\TodoRepository::class
        );

        // Stock Repositories
        $this->app->bind(
            \App\Repositories\Interfaces\StockRepositoryInterface::class,
            \App\Repositories\StockRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\SupplierRepositoryInterface::class,
            \App\Repositories\SupplierRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ClinicRepositoryInterface::class,
            \App\Repositories\ClinicRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\StockRequestRepositoryInterface::class,
            \App\Repositories\StockRequestRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\StockTransactionRepositoryInterface::class,
            \App\Repositories\StockTransactionRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\StockAlertRepositoryInterface::class,
            \App\Repositories\StockAlertRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // 🛡️ CRITICAL FIX: StockTransaction Observer'ı register et
        StockTransaction::observe(StockTransactionObserver::class);

        // 📧 StockAlert Observer - Mail bildirimleri için
        StockAlert::observe(StockAlertObserver::class);

        // Stok seviyesi değiştiğinde tetiklenecek listener'lar
        Event::listen(StockLevelChanged::class, CheckStockAlertsListener::class);
        Event::listen(StockLevelChanged::class, ClearStockCacheListener::class);

        Gate::policy(\App\Models\Stock::class, \App\Policies\StockPolicy::class);
        Gate::policy(\App\Models\Clinic::class, \App\Policies\ClinicPolicy::class);

        // Implicitly grant "Super Admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
