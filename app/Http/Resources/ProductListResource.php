<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'unit' => $this->unit,
            'category' => $this->category,
            'brand' => $this->brand,
            'min_stock_level' => $this->min_stock_level,
            'critical_stock_level' => $this->critical_stock_level,
            'total_stock' => $this->total_stock,
            'status' => $this->stock_status,
            'is_active' => $this->is_active,
            'show_zero_stock_in_critical' => $this->show_zero_stock_in_critical,
            'clinic_name' => $this->clinic?->name,
            'clinics' => $this->clinic?->name ? [$this->clinic->name] : [],
            'batches_count' => $this->batches_count ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
