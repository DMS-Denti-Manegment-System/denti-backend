<?php

namespace App\Modules\Stock\Resources;

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
            'total_stock' => $this->total_stock,
            'current_stock' => $this->total_stock, // Alias for compatibility
            'code' => $this->sku, // Alias for compatibility
            'status' => $this->stock_status,
            'is_active' => $this->is_active,
            'clinic_id' => $this->clinic_id,
            'clinic_name' => $this->clinic?->name,
            'has_expiration_date' => $this->has_expiration_date,
            'batches' => StockResource::collection($this->whenLoaded('batches'))->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
