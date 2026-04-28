<?php

namespace App\Modules\Stock\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'quantity' => $this->quantity,
            'previous_stock' => $this->previous_stock,
            'new_stock' => $this->new_stock,
            'performed_by' => $this->performed_by,
            'description' => $this->description,
            'notes' => $this->notes,
            'transaction_date' => $this->transaction_date ? $this->transaction_date->format('Y-m-d H:i:s') : null,
            'is_sub_unit' => $this->is_sub_unit,
        ];
    }
}
