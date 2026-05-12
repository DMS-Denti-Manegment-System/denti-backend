<?php

namespace App\Observers;

use App\Models\StockTransaction;

class StockTransactionObserver
{
    /**
     * Handle the StockTransaction "created" event.
     * Triggers whenever a new transaction is logged.
     */
    public function created(StockTransaction $transaction): void
    {
        // Loglama veya bildirim işlemleri burada yapılabilir.
        // Stok güncelleme mantığı StockService içine taşındı.
    }

    public function deleted(StockTransaction $transaction): void
    {
        // Transaction deletion is no longer a stock mutation path.
        // Reversals must be represented by an explicit opposite transaction.
    }
}
