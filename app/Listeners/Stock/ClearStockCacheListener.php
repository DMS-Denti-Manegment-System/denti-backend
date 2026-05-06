<?php

namespace App\Listeners\Stock;

use App\Events\Stock\StockLevelChanged;
use Illuminate\Support\Facades\Cache;

/**
 * StockLevelChanged event'ini dinler ve stok istatistik cache'ini temizler.
 * StockService'den bu sorumluluğu devraldı (God Object azaltma).
 *
 * Cache Thrashing Notu:
 * Önceki yaklaşımda her stok hareketinde cache anında siliniyordu.
 * Yeni yaklaşım: Cache silinmez, 5 dakika sonra otomatik expire olur.
 * Bu "eventual consistency" kabul ederek cache thrashing'i önler.
 * Kritik durumlarda (örn. stok bitince) alert sistemi devreye girer.
 */
class ClearStockCacheListener
{
    public function handle(StockLevelChanged $event): void
    {
        Cache::forget("stock_stats_{$event->companyId}_all");
        if ($event->clinicId) {
            Cache::forget("stock_stats_{$event->companyId}_{$event->clinicId}");
        }
    }
}
