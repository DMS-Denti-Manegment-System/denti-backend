<?php

namespace App\Observers;

use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;

class StockTransactionObserver
{
    /**
     * Handle the StockTransaction "created" event.
     * Triggers whenever a new transaction is logged.
     */
    public function created(StockTransaction $transaction): void
    {
        $this->updateStockLevel($transaction, 'apply');
    }

    /**
     * Handle the StockTransaction "deleted" event.
     * Reverses the impact if a transaction is removed.
     */
    public function deleted(StockTransaction $transaction): void
    {
        $this->updateStockLevel($transaction, 'reverse');
    }

    /**
     * Main logic to update the Stock model based on transaction data.
     */
    private function updateStockLevel(StockTransaction $transaction, string $action): void
    {
        $stock = $transaction->stock;
        if (!$stock) {
            return;
        }

        // 1. Determine direction based on transaction type
        // entry/adjustment_plus -> Increment (+)
        // usage/loss/adjustment_minus -> Decrement (-)
        $isPositive = match ($transaction->type) {
            'entry', 'adjustment_plus', 'adjustment_increase', 'purchase', 'transfer_in', 'returned', 'return_in' => true,
            'usage', 'loss', 'adjustment_minus', 'adjustment_decrease', 'transfer_out', 'expired', 'damaged', 'return_out' => false,
            default => null,
        };

        if ($isPositive === null) {
            return;
        }

        // If we are reversing (deleting), flip the direction
        if ($action === 'reverse') {
            $isPositive = !$isPositive;
        }

        $quantity = (int) $transaction->quantity;

        // 2. Handle Sub-Unit Logic
        // If the transaction is in sub-units, we need to handle the conversion
        if ($transaction->is_sub_unit && $stock->has_sub_unit && $stock->sub_unit_multiplier > 0) {
            $this->handleSubUnitCalculation($stock, $quantity, $isPositive);
        } else {
            // 3. Handle Base Unit Logic (Atomic Updates)
            if ($isPositive) {
                $stock->increment('current_stock', $quantity);
            } else {
                $stock->decrement('current_stock', $quantity);
            }
            
            // Sync available_stock (current - reserved)
            $stock->updateQuietly([
                'available_stock' => $stock->current_stock - $stock->reserved_stock
            ]);
        }

        // Trigger alert check for any stock change
        app(\App\Services\StockAlertService::class)->checkAndCreateAlerts($stock);

        // Dispatch Event for real-time updates
        \App\Events\Stock\StockLevelChanged::dispatch($stock->fresh(), $stock->company_id, $stock->clinic_id);
    }

    /**
     * Handles stock calculation when sub-units are involved.
     * Converts sub-unit quantity and manages overflow/underflow to the base unit.
     */
    private function handleSubUnitCalculation($stock, int $quantity, bool $isPositive): void
    {
        $multiplier = (int) $stock->sub_unit_multiplier;
        
        // Load fresh data to ensure we have the latest sub-stock levels
        $stock->refresh();

        if ($isPositive) {
            $totalSubUnits = $stock->current_sub_stock + $quantity;
            $extraBaseUnits = (int) floor($totalSubUnits / $multiplier);
            $newSubStock = $totalSubUnits % $multiplier;

            if ($extraBaseUnits > 0) {
                $stock->increment('current_stock', $extraBaseUnits);
            }
            $stock->current_sub_stock = $newSubStock;
        } else {
            $currentTotalSub = ($stock->current_stock * $multiplier) + $stock->current_sub_stock;
            $newTotalSub = max(0, $currentTotalSub - $quantity);

            $stock->current_stock = (int) floor($newTotalSub / $multiplier);
            $stock->current_sub_stock = $newTotalSub % $multiplier;
        }

        // Sync available stock and save sub-unit changes
        $stock->available_stock = $stock->current_stock - $stock->reserved_stock;
        $stock->saveQuietly();
    }
}
