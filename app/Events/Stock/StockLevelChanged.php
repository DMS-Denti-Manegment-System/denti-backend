<?php

namespace App\Events\Stock;

use App\Modules\Stock\Models\Stock;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stok miktarı değiştiğinde fırlatılır.
 * Listener'lar: CheckStockAlertsListener, ClearStockCacheListener
 */
class StockLevelChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Stock $stock,
        public readonly int   $companyId,
        public readonly ?int  $clinicId
    ) {}
}
