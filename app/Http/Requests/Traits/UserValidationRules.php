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
     * Role validation rule with company constraint.
     */
    protected function roleRule(?int $companyId): array
    {
        return [
            'required',
            'integer',
            Rule::exists('roles', 'id')->where(function ($query) use ($companyId) {
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                $query->where('name', '!=', 'Super Admin');
            }),
        ];
    }
}
