<?php

namespace App\Modules\Stock\Jobs;

use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Services\StockAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckStockLevelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $stock;

    public function __construct(Stock $stock)
    {
        $this->stock = $stock;
    }

    public function handle(StockAlertService $stockAlertService)
    {
        $stockAlertService->checkAndCreateAlerts($this->stock);
    }
}