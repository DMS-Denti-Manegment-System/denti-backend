<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use UserValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users'),
            ],
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
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
