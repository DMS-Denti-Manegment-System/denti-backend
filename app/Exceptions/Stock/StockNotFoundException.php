<?php

namespace App\Exceptions\Stock;

use RuntimeException;

class StockNotFoundException extends RuntimeException
{
    public function __construct(int $id = null)
    {
        $message = $id
            ? "Stok bulunamadı (ID: {$id})"
            : 'Stok bulunamadı';

        parent::__construct($message, 404);
    }
}
