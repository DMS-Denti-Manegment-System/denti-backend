<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_code' => 'sometimes|required|string|max:20',
            'company_code' => 'sometimes|required|string|max:20',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ];
    }
}
