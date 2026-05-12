<?php

namespace App\Jobs;

use App\Models\Stock;
use App\Services\StockAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckStockLevelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $stockId;

    public function __construct(int $stockId)
    {
        $this->stockId = $stockId;
    }

    public function handle(StockAlertService $stockAlertService)
    {
        $stock = Stock::query()->find($this->stockId);

        if ($stock) {
            $stockAlertService->checkAndCreateAlerts($stock);
        }
    }
}
