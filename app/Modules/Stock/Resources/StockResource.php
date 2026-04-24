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
        // Model'de olmayan appends özelliklerini anlık olarak burada hesaplıyoruz
        $totalBaseUnits = $this->getTotalBaseUnits();
        $stockStatus = $this->getStockStatus($totalBaseUnits);
        $isExpired = $this->getIsExpired();
        $isNearExpiry = $this->getIsNearExpiry();
        $daysToExpiry = $this->getDaysToExpiry();

        return array_merge(parent::toArray($request), [
            'total_base_units' => $totalBaseUnits,
            'stock_status'     => $stockStatus,
            'is_expired'       => $isExpired,
            'is_near_expiry'   => $isNearExpiry,
            'days_to_expiry'   => $daysToExpiry,
            
            // İlişkileri de eklenebilir, eager load edilmişlerse otomatik gelir
            // ancak parent::toArray($request) zaten model üzerindeki yüklü ilişkileri de alır.
        ]);
    }

    private function getTotalBaseUnits()
    {
        if (!$this->has_sub_unit) {
            return $this->current_stock;
        }
        return ($this->current_stock * ($this->sub_unit_multiplier ?? 1)) + $this->current_sub_stock;
    }

    private function getStockStatus($totalBaseUnits)
    {
        if (!$this->is_active) return 'inactive';
        
        $redLevel = $this->red_alert_level ?? $this->critical_stock_level;
        $yellowLevel = $this->yellow_alert_level ?? $this->min_stock_level;

        if ($totalBaseUnits <= $redLevel) return 'critical';
        if ($totalBaseUnits <= $yellowLevel) return 'low';
        return 'normal';
    }

    private function getIsExpired()
    {
        return $this->track_expiry && $this->expiry_date && $this->expiry_date < now();
    }

    private function getIsNearExpiry()
    {
        return $this->track_expiry &&
               $this->expiry_date &&
               $this->expiry_date <= now()->addDays(30) &&
               $this->expiry_date > now();
    }

    private function getDaysToExpiry()
    {
        if (!$this->track_expiry || !$this->expiry_date) return null;
        return now()->diffInDays($this->expiry_date, false);
    }
}
