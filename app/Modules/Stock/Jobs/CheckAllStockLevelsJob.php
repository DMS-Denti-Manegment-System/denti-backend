<?php

namespace App\Modules\Stock\Jobs;

use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Services\StockAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckAllStockLevelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(StockAlertService $stockAlertService)
    {
        $stocks = Stock::active()->get();

        foreach ($stocks as $stock) {
            $stockAlertService->checkAndCreateAlerts($stock);
        }

        \Log::info('All stock levels checked', ['count' => $stocks->count()]);
    }
}