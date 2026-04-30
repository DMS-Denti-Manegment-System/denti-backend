<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'product_id' => $this->product_id,
            'name' => $this->product?->name,
            'code' => $this->product?->sku,
            'unit' => $this->product?->unit,
            'category' => $this->product?->category,
            'brand' => $this->product?->brand,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->supplier?->name,
            'purchase_price' => $this->purchase_price,
            'currency' => $this->currency,
            'purchase_date' => $this->purchase_date,
            'expiry_date' => $this->expiry_date,
            'current_stock' => $this->current_stock,
            'reserved_stock' => $this->reserved_stock,
            'available_stock' => $this->available_stock,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'track_expiry' => $this->track_expiry,
            'clinic_id' => $this->clinic_id,
            'clinic_name' => $this->clinic?->name,
            'storage_location' => $this->storage_location,
            'has_sub_unit' => $this->has_sub_unit,
            'sub_unit_name' => $this->sub_unit_name,
            'sub_unit_multiplier' => $this->sub_unit_multiplier,
            'current_sub_stock' => $this->current_sub_stock,
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'sku' => $this->product?->sku,
                'brand' => $this->product?->brand,
                'category' => $this->product?->category,
                'unit' => $this->product?->unit,
                'has_expiration_date' => $this->product?->has_expiration_date,
            ],
            'total_base_units' => $this->total_base_units,
            'stock_status'     => $this->stock_status,
            'is_expired'       => $this->is_expired,
            'is_near_expiry'   => $this->is_near_expiry,
            'days_to_expiry'   => $this->days_to_expiry,
        ];
    }
}
