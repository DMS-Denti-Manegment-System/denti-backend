<?php

namespace Database\Seeders;

use App\Services\RoleService;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(RoleService::class)->ensureRoles();
    }
}
