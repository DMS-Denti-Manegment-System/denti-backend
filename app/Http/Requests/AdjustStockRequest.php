<?php

namespace App\Http\Requests;

use App\Traits\JsonResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdjustStockRequest extends FormRequest
{
    use JsonResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:increase,decrease,sync',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_sub_unit' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error('Validation error', 422, $validator->errors()));
    }
}
