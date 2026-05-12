<?php

namespace Database\Seeders;

use App\Services\CompanyRoleService;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(CompanyRoleService::class)->ensureRoles();
    }
}
