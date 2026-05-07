<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'product_id' => [
                'required',
                new \App\Rules\CompanyOwned('products'),
            ],
            'supplier_id' => [
                'required',
                new \App\Rules\CompanyOwned('suppliers'),
            ],
            'clinic_id' => [
                'required',
                new \App\Rules\CompanyOwned('clinics'),
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
