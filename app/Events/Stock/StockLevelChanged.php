<?php

namespace App\Events\Stock;

use App\Models\Stock;
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
        ?int $companyId = null,
        ?int $clinicId = null
    ) {
        $this->companyId = $companyId ?? $stock->company_id;
        $this->clinicId = $clinicId ?? $stock->clinic_id;
    }
}
