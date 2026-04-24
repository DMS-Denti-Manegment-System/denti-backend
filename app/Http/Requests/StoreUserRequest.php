<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

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
        $companyId = auth()->user()->company_id;

        return array_merge($this->commonRules(), [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => $this->roleRule($companyId),
            'company_id' => 'nullable|integer',
        ]);
    }
}
