<?php

namespace App\Providers;

use App\Events\Stock\StockLevelChanged;
use App\Listeners\Stock\ClearStockCacheListener;
use App\Models\Clinic;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\DashboardStatsObserver;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // 🛡️ CRITICAL FIX: StockTransaction Observer'ı register et
        StockTransaction::observe(StockTransactionObserver::class);

        // 📧 Observers
        Product::observe(DashboardStatsObserver::class);
        Clinic::observe(DashboardStatsObserver::class);
        Supplier::observe(DashboardStatsObserver::class);
        User::observe(DashboardStatsObserver::class);

        // Event Listeners
        Event::listen(StockLevelChanged::class, ClearStockCacheListener::class);

        Gate::policy(\App\Models\Stock::class, \App\Policies\StockPolicy::class);
        Gate::policy(\App\Models\Clinic::class, \App\Policies\ClinicPolicy::class);

        // 🛡️ SUPER ADMIN BYPASS: Admin rolüne sahip kullanıcılara tüm yetkileri ver
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Admin') ? true : null;
        });
    }
}
