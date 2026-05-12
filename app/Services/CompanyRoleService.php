<?php

namespace App\Services;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CompanyRoleService
{
    public function ensureRoles(): void
    {
        $this->ensurePermissions();

        foreach ($this->rolePermissions() as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions === ['*'] ? Permission::all() : $permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermissions(): void
    {
        foreach (PermissionCatalog::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    private function rolePermissions(): array
    {
        return [
            'Admin' => ['*'],
            'Stock Manager' => [
                'view-stocks', 'create-stocks', 'update-stocks', 'delete-stocks',
                'adjust-stocks', 'use-stocks',
                'transfer-stocks', 'approve-transfers', 'cancel-transfers',
                'view-clinics', 'view-reports', 'export-reports', 'view-audit-logs',
                'view-todos', 'manage-todos',
            ],
            'Clinic Manager' => [
                'view-stocks', 'create-stocks', 'update-stocks', 'delete-stocks',
                'adjust-stocks', 'use-stocks',
                'transfer-stocks', 'approve-transfers', 'cancel-transfers',
                'view-clinics', 'create-clinics', 'update-clinics', 'delete-clinics',
                'view-reports', 'export-reports', 'view-audit-logs',
                'manage-users',
                'view-todos', 'manage-todos',
            ],
            'Doctor' => [
                'view-stocks', 'use-stocks', 'view-clinics',
                'view-reports',
                'view-todos', 'manage-todos',
            ],
            'Secretary' => [
                'view-stocks', 'use-stocks', 'view-clinics',
                'view-reports',
                'view-todos', 'manage-todos',
            ],
        ];
    }
}
