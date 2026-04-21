<?php

namespace App\Modules\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:stocks,code',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'supplier_id' => 'required|exists:suppliers,id',
            'clinic_id' => 'required|exists:clinics,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:today',
            'current_stock' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'critical_stock_level' => 'required|integer|min:0',
            'yellow_alert_level' => 'nullable|integer|min:0',
            'red_alert_level' => 'nullable|integer|min:0',
            'track_expiry' => 'boolean',
            'track_batch' => 'boolean',
            'storage_location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'has_sub_unit' => 'boolean',
            'sub_unit_name' => 'nullable|required_if:has_sub_unit,true|string|max:50',
            'sub_unit_multiplier' => 'nullable|required_if:has_sub_unit,true|integer|min:1',
            'current_sub_stock' => 'nullable|integer|min:0'
        ];
    }
}
