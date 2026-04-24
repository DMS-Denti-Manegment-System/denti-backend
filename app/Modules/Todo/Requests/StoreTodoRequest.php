<?php

namespace App\Modules\Todo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\JsonResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTodoRequest extends FormRequest
{
    use JsonResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error('Validation error', 422, $validator->errors()));
    }
}
