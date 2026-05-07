<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockAlertResource extends JsonResource
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
            'type' => $this->type,
            'severity' => $this->severity,
            'title' => $this->title,
            'message' => $this->message,
            'stock_id' => $this->stock_id,
            'clinic_id' => $this->clinic_id,
            'threshold_level' => $this->threshold_level,
            'current_stock_level' => $this->current_stock_level,
            'expiry_date' => $this->expiry_date,
            'days_until_expiry' => $this->days_until_expiry,
            'status' => $this->status,
            'is_resolved' => $this->is_resolved,
            'resolved_by' => $this->resolved_by,
            'resolved_at' => $this->resolved_at,
            'resolution_notes' => $this->resolution_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations
            'stock' => $this->stock ? [
                'id' => $this->stock->id,
                'name' => $this->stock->name ?? $this->stock->product?->name,
                'current_stock' => $this->stock->current_stock,
                'min_stock_level' => $this->stock->product?->min_stock_level,
                'critical_stock_level' => $this->stock->product?->critical_stock_level,
                'unit' => $this->stock->product?->unit,
                'category' => $this->stock->product?->category,
                'brand' => $this->stock->product?->brand,
                'storage_location' => $this->stock->storage_location,
            ] : null,
            'clinic' => $this->clinic ? [
                'id' => $this->clinic->id,
                'name' => $this->clinic->name,
                'specialty_code' => $this->clinic->specialty_code,
            ] : null,
        ];
    }
}
