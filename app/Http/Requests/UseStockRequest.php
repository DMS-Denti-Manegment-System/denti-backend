<?php

namespace App\Http\Requests;

use App\Traits\JsonResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UseStockRequest extends FormRequest
{
    use JsonResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:500',
            'used_by' => 'nullable|string|max:255',
            'is_from_reserved' => 'nullable|boolean',
            'is_sub_unit' => 'nullable|boolean',
            'show_zero_stock_in_critical' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error('Validation error', 422, $validator->errors()));
    }
}
