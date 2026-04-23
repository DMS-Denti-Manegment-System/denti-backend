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
        // Cache süresini kısalttık (15dk → 5dk) ama anında silmiyoruz.
        // Stok istatistikleri maksimum 5 dakika gecikmeli olabilir — bu kabul edilebilir.
        // Anlık doğruluk kritikse API doğrudan DB'den çekiyor zaten.

        // YALNIZCA çok kritik bir stok hareketi (sıfıra düşme gibi) durumunda
        // anında invalidate etmek istersen aşağıdaki satırı aktif et:
        // Cache::forget("stock_stats_{$event->companyId}_all");
        // Cache::forget("stock_stats_{$event->companyId}_{$event->clinicId}");
    }
}
