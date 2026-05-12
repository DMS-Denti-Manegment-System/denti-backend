<?php

namespace App\Events\Stock;

use App\Models\Stock;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stok miktarı değiştiğinde fırlatılır.
 * Listener'lar: ClearStockCacheListener
 */
class StockLevelChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public readonly ?int $clinicId;

    public function __construct(
        public readonly Stock $stock,
        ?int $clinicId = null
    ) {
        $this->clinicId = $clinicId ?? $stock->clinic_id;
    }
}
