<?php

namespace App\Modules\Stock;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class StockServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Repository bindings
        $this->app->bind(
            \App\Modules\Stock\Repositories\Interfaces\StockRepositoryInterface::class,
            \App\Modules\Stock\Repositories\StockRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Repositories\Interfaces\SupplierRepositoryInterface::class,
            \App\Modules\Stock\Repositories\SupplierRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Repositories\Interfaces\ClinicRepositoryInterface::class,
            \App\Modules\Stock\Repositories\ClinicRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Repositories\Interfaces\StockRequestRepositoryInterface::class,
            \App\Modules\Stock\Repositories\StockRequestRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Repositories\Interfaces\StockTransactionRepositoryInterface::class,
            \App\Modules\Stock\Repositories\StockTransactionRepository::class
        );

        $this->app->bind(
            \App\Modules\Stock\Repositories\Interfaces\StockAlertRepositoryInterface::class,
            \App\Modules\Stock\Repositories\StockAlertRepository::class
        );
    }

    public function boot()
    {
        // Routes yükleme
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');

        // Migrations yükleme
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Schedule tanımlama
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Her gün stok seviyelerini kontrol et
            $schedule->job(\App\Modules\Stock\Jobs\CheckAllStockLevelsJob::class)
                    ->dailyAt('09:00')
                    ->description('Check all stock levels and create alerts');

            // Her hafta süresi yaklaşan ürünleri kontrol et
            $schedule->job(\App\Modules\Stock\Jobs\CheckExpiringItemsJob::class)
                    ->weeklyOn(1, '10:00')
                    ->description('Check expiring items');
        });
    }
}