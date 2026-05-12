<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id'),
            ],
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id'),
            ],
            'clinic_id' => [
                'required',
                Rule::exists('clinics', 'id'),
            ],
            'purchase_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'expiry_yellow_days' => 'nullable|integer|min:1',
            'expiry_red_days' => 'nullable|integer|min:1',
            'current_stock' => 'required|integer|min:0',
            'track_expiry' => 'boolean',
            'track_batch' => 'boolean',
            'storage_location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'has_sub_unit' => 'boolean',
            'sub_unit_name' => 'nullable|required_if:has_sub_unit,true|string|max:50',
            'sub_unit_multiplier' => 'nullable|required_if:has_sub_unit,true|integer|min:1',
            'current_sub_stock' => 'nullable|integer|min:0',
        ];
    }
}
