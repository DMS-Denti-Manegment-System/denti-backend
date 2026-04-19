<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware will handle authorization
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:companies,domain',
            'subscription_plan' => 'required|string|in:free,basic,pro,enterprise',
            'max_users' => 'required|integer|min:1',
            'status' => 'required|string|in:active,inactive,suspended',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
        ];
    }
}
