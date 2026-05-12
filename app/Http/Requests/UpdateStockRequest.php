<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Batch specific fields
            'batch_code' => 'nullable|string|max:50',
            'supplier_id' => [
                'sometimes',
                'required',
                Rule::exists('suppliers', 'id'),
            ],
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'storage_location' => 'nullable|string|max:255',
            'is_active' => 'boolean',

            // Notification thresholds for this batch
            'expiry_yellow_days' => 'nullable|integer|min:0',
            'expiry_red_days' => 'nullable|integer|min:0',

            // Sub-unit status
            'has_sub_unit' => 'boolean',
            'sub_unit_name' => 'nullable|string|max:50',
            'sub_unit_multiplier' => 'nullable|integer|min:1',
            'current_sub_stock' => 'nullable|integer|min:0',
        ];
    }
}
