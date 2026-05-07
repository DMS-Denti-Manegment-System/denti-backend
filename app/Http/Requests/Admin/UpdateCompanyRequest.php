<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:20|unique:companies,code,'.$companyId,
            'domain' => 'nullable|string|max:255|unique:companies,domain,'.$companyId,
            'subscription_plan' => 'sometimes|required|string|in:basic,standard,premium',
            'max_users' => 'sometimes|required|integer|min:1',
            'status' => 'sometimes|required|string|in:active,inactive,suspended',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }
    }
}
