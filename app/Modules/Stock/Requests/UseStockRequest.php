<?php

namespace App\Modules\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\JsonResponseTrait;
use Illuminate\Contracts\Validation\Validator;
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
            'quantity' => 'required|integer|min:1',
            'reason'   => 'required|string|max:500',
            'used_by'  => 'nullable|string|max:255',
            'notes'    => 'nullable|string|max:1000'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error('Validation error', 422, $validator->errors()));
    }
}
