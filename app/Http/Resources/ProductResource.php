<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'unit' => $this->unit,
            'category' => $this->category,
            'brand' => $this->brand,
            'min_stock_level' => $this->min_stock_level,
            'critical_stock_level' => $this->critical_stock_level,
            'yellow_alert_level' => $this->yellow_alert_level,
            'red_alert_level' => $this->red_alert_level,
            'total_stock' => $this->total_stock,
            'current_stock' => $this->total_stock, // Alias for compatibility
            'code' => $this->sku, // Alias for compatibility
            'status' => $this->stock_status,
            'is_active' => $this->is_active,
            'clinic_id' => $this->clinic_id,
            'clinic_name' => $this->clinic?->name,
            'clinics' => $this->batches->pluck('clinic.name')->unique()->filter()->values(),
            'has_expiration_date' => $this->has_expiration_date,

            // Finansal Bilgiler
            'average_cost' => $this->averageCost,
            'last_purchase_price' => $this->lastPurchasePrice,
            'total_stock_value' => $this->totalStockValue,
            'potential_revenue' => $this->potentialRevenue,
            'potential_profit' => $this->potentialProfit,
            'profit_margin' => $this->profitMargin,

            // Transaction Summary
            'total_in' => $this->totalIn,
            'total_out' => $this->totalOut,

            'batches' => StockResource::collection($this->whenLoaded('batches')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
