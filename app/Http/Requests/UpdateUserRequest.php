<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use UserValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => $this->permissionsRule(),
            'permissions.*' => 'string|exists:permissions,name',
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id'),
            ],
        ]);
    }
}
