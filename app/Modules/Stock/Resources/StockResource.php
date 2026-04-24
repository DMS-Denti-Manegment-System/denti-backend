<?php

namespace App\Modules\Stock\Resources;

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
        return array_merge(parent::toArray($request), [
            // Model üzerindeki accessor'ları kullanıyoruz (DRY)
            'total_base_units' => $this->total_base_units,
            'stock_status'     => $this->stock_status,
            'is_expired'       => $this->is_expired,
            'is_near_expiry'   => $this->is_near_expiry,
            'days_to_expiry'   => $this->days_to_expiry,
        ]);
    }
}
