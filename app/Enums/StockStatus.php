<?php

namespace App\Enums;

enum StockStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';
    case OUT_OF_STOCK = 'out_of_stock';
    case LOW_STOCK = 'low_stock';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::INACTIVE => 'Pasif',
            self::DELETED => 'Silindi',
            self::OUT_OF_STOCK => 'Stokta Yok',
            self::LOW_STOCK => 'Düşük Stok',
        };
    }
}
