<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermissionTo('create-stocks');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku'),
            ],
            'description' => 'nullable|string',
            'unit' => 'required|string|max:20',
            'category' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'min_stock_level' => 'nullable|integer|min:0',
            'critical_stock_level' => 'nullable|integer|min:0',
            'yellow_alert_level' => 'nullable|integer|min:0',
            'red_alert_level' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'has_expiration_date' => 'nullable|boolean',
            'quantity' => 'required|numeric|min:0', // 🚀 Renamed from initial_stock
            'clinic_id' => [
                'required', // 🚀 Made required as it's essential for stock creation
                'integer',
                Rule::exists('clinics', 'id'),
            ],
            'supplier_id' => [
                'required', // 🚀 Made required as per UI labels
                'integer',
                Rule::exists('suppliers', 'id'),
            ],
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'expiry_yellow_days' => 'nullable|integer|min:0',
            'expiry_red_days' => 'nullable|integer|min:0',
            'storage_location' => 'nullable|string|max:100',
            'has_sub_unit' => 'nullable|boolean',
            'sub_unit_name' => 'nullable|string|max:50',
            'sub_unit_multiplier' => 'nullable|integer|min:1',
            'show_zero_stock_in_critical' => 'nullable|boolean',
        ];
    }
}
