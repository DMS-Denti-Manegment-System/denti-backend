<?php

namespace App\Jobs;

use App\Models\Stock;
use App\Services\StockAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckAllStockLevelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * İşlem başarısız olursa kaç kez tekrar deneneceği.
     */
    public int $tries = 3;

    /**
     * Tekrar denemeden önce beklenecek saniye.
     */
    public int $backoff = 60;

    public function handle(StockAlertService $stockAlertService)
    {
        $lowStocks = [];
        // Aynı ürünün her parti (stock) satırı için alert hesabı aynı sonucu verir;
        // product.batches eager load olmazsa her satırda ekstra DB sorgusu patlar (N+1).
        $seenProductIds = [];

        foreach (
            Stock::active()
                ->with(['product.batches', 'clinic.company'])
                ->orderBy('product_id')
                ->cursor() as $stock
        ) {
            if (isset($seenProductIds[$stock->product_id])) {
                continue;
            }
            $seenProductIds[$stock->product_id] = true;

            $alerts = $stockAlertService->checkAndGetAlerts($stock);
            if (! empty($alerts)) {
                $companyId = $stock->company_id;
                $lowStocks[$companyId][] = [
                    'stock' => $stock,
                    'alerts' => $alerts,
                ];
            }
        }

        // Send Digest Notifications per Company
        foreach ($lowStocks as $companyId => $items) {
            $stockAlertService->sendDigestNotification($companyId, $items);
        }

        \Log::info('All stock levels checked and digests sent.');
    }
}
