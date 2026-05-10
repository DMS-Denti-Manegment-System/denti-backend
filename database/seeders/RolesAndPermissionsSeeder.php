<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = PermissionCatalog::all();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $all = Permission::all();

        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->syncPermissions($all);

        $owner = Role::findOrCreate('Company Owner', 'web');
        $owner->syncPermissions($all);

        // Kod tabanında (StockRequestService vb.) kullanılıyor
        $admin = Role::findOrCreate('Admin', 'web');
        $admin->syncPermissions($all);

        $stockManager = Role::findOrCreate('Stock Manager', 'web');
        $stockManager->syncPermissions([
            'view-stocks', 'create-stocks', 'update-stocks', 'delete-stocks',
            'adjust-stocks', 'use-stocks',
            'transfer-stocks', 'approve-transfers', 'cancel-transfers',
            'view-clinics', 'view-reports', 'export-reports', 'view-audit-logs',
            'view-todos', 'manage-todos',
        ]);

        $manager = Role::findOrCreate('Clinic Manager', 'web');
        $manager->syncPermissions([
            'view-stocks', 'create-stocks', 'update-stocks', 'delete-stocks',
            'adjust-stocks', 'use-stocks',
            'transfer-stocks', 'approve-transfers', 'cancel-transfers',
            'view-clinics', 'create-clinics', 'update-clinics', 'delete-clinics',
            'view-reports', 'export-reports', 'view-audit-logs',
            'manage-users', 'manage-company',
            'view-todos', 'manage-todos',
        ]);

        $doctor = Role::findOrCreate('Doctor', 'web');
        $doctor->syncPermissions([
            'view-stocks', 'use-stocks', 'view-clinics',
            'view-reports',
            'view-todos', 'manage-todos',
        ]);

        $secretary = Role::findOrCreate('Secretary', 'web');
        $secretary->syncPermissions([
            'view-stocks', 'use-stocks', 'view-clinics',
            'view-reports',
            'view-todos', 'manage-todos',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
