<?php

namespace App\Exceptions\Stock;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(int $available, int $requested)
    {
        parent::__construct(
            "Yetersiz stok. Mevcut miktar: {$available}, Talep edilen: {$requested}",
            400
        );
    }
}
