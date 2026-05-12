<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class StockStatsCache
{
    public static function version(): int
    {
        return (int) Cache::get(self::versionKey(), 1);
    }

    public static function bump(): void
    {
        $key = self::versionKey();
        if (! Cache::has($key)) {
            Cache::forever($key, 2);

            return;
        }

        Cache::increment($key);
    }

    private static function versionKey(): string
    {
        return 'stock_stats_version';
    }
}
