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
        $companyId = auth()->user()->company_id;

        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'description' => 'nullable|string',
            'unit' => 'required|string|max:20',
            'category' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:50',
            'min_stock_level' => 'nullable|integer|min:0',
            'critical_stock_level' => 'nullable|integer|min:0',
            'yellow_alert_level' => 'nullable|integer|min:0',
            'red_alert_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'has_expiration_date' => 'boolean',
            'initial_stock' => 'nullable|numeric|min:0',
            'clinic_id' => [
                'nullable',
                'integer',
                new \App\Rules\CompanyOwned('clinics'),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                new \App\Rules\CompanyOwned('suppliers'),
            ],
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'storage_location' => 'nullable|string|max:100',
            'has_sub_unit' => 'boolean',
            'sub_unit_name' => 'nullable|string|max:50',
            'sub_unit_multiplier' => 'nullable|integer|min:1',
        ];
    }
}
