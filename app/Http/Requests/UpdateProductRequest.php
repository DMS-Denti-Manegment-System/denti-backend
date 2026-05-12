<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermissionTo('update-stocks');
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->ignore($product?->id),
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
            'show_zero_stock_in_critical' => 'nullable|boolean',

            // Sub-unit settings
            'has_sub_unit' => 'nullable|boolean',
            'sub_unit_name' => 'nullable|string|max:50',
            'sub_unit_multiplier' => 'nullable|integer|min:1',
        ];
    }
}
