<?php

namespace App\Listeners\Stock;

use App\Events\Stock\StockLevelChanged;
use App\Modules\Stock\Services\StockAlertService;

/**
 * StockLevelChanged event'ini dinler ve stok seviyesi kontrolünü tetikler.
 * StockService'den bu sorumluluğu devraldı (God Object azaltma).
 */
class CheckStockAlertsListener
{
    public function __construct(private StockAlertService $alertService) {}

    public function handle(StockLevelChanged $event): void
    {
        $this->alertService->checkAndCreateAlerts($event->stock);
    }
}
