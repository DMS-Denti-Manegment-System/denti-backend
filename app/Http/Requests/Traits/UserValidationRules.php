<?php

namespace App\Http\Requests\Traits;

use Illuminate\Validation\Rule;

trait UserValidationRules
{
    /**
     * Common validation rules for users.
     */
    protected function commonRules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Permissions validation rule.
     */
    protected function permissionsRule(): array
    {
        return [
            'nullable',
            'array',
            'max:200',
        ];
    }
}
