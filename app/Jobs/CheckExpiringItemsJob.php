<?php

namespace App\Jobs;

use App\Models\Stock;
use App\Services\StockAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckExpiringItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(StockAlertService $stockAlertService)
    {
        // Son kullanma tarihi takip edilen ürünleri kontrol et
        $stocks = Stock::active()
            ->where('track_expiry', true)
            ->whereNotNull('expiry_date')
            ->get();

        foreach ($stocks as $stock) {
            $stockAlertService->checkAndCreateAlerts($stock);
        }

        \Log::info('Expiring items checked', ['count' => $stocks->count()]);
    }
}
