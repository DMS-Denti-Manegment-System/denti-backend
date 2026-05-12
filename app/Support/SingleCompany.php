<?php

namespace App\Support;

use App\Models\Company;

class SingleCompany
{
    public function company(): Company
    {
        return Company::query()->firstOrCreate(
            ['code' => config('denti.company.code', 'default')],
            [
                'name' => config('denti.company.name', 'Denti Klinik'),
                'domain' => config('denti.company.domain', 'local'),
                'email' => config('denti.company.email'),
                'subscription_plan' => 'single',
                'max_users' => config('denti.company.max_users', 25),
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    public function id(): int
    {
        return (int) $this->company()->getKey();
    }
}
