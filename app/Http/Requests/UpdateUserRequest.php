<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

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
        $companyId = auth()->user()->company_id;

        return array_merge($this->commonRules(), [
            'is_active' => ['sometimes', 'boolean'],
            'role_id' => $this->roleRule($companyId),
        ]);
    }
}
