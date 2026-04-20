<?php

namespace App\Modules\Stock\Services;

class StockCalculatorService
{
    /**
     * Calculate new stock levels for sub-unit adjustments.
     * Returns an array with current_stock and current_sub_stock.
     */
    public function calculateAdjustment(
        int $currentStock,
        int $currentSubStock,
        int $quantity,
        int $multiplier
    ): array {
        $newSubStock = $currentSubStock + $quantity;
        $newMainStock = $currentStock;

        if ($newSubStock >= $multiplier) {
            $boxesToAdd = (int) floor($newSubStock / $multiplier);
            $newMainStock += $boxesToAdd;
            $newSubStock = $newSubStock % $multiplier;
        } elseif ($newSubStock < 0) {
            $boxesToTake = (int) ceil(abs($newSubStock) / $multiplier);
            $newMainStock -= $boxesToTake;
            $newSubStock = ($boxesToTake * $multiplier) + $newSubStock;
        }

        return [
            'current_stock' => $newMainStock,
            'current_sub_stock' => $newSubStock,
        ];
    }

    /**
     * Calculate new stock levels for sub-unit usage.
     * Returns an array with current_stock and current_sub_stock, or null if insufficient.
     */
    public function calculateUsage(
        int $currentStock,
        int $currentSubStock,
        int $quantity,
        int $multiplier
    ): ?array {
        if ($currentSubStock >= $quantity) {
            return [
                'current_stock' => $currentStock,
                'current_sub_stock' => $currentSubStock - $quantity,
            ];
        }

        $deficit = $quantity - $currentSubStock;
        $boxesToOpen = (int) ceil($deficit / $multiplier);

        if ($currentStock < $boxesToOpen) {
            return null;
        }

        return [
            'current_stock' => $currentStock - $boxesToOpen,
            'current_sub_stock' => $currentSubStock + ($boxesToOpen * $multiplier) - $quantity,
        ];
    }
}
