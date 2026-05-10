<?php

namespace App\Services;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CompanyRoleService
{
    public function ensureSystemRoles(): void
    {
        $this->ensurePermissions();
        setPermissionsTeamId(0);

        $role = Role::withoutGlobalScopes()->firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
            'company_id' => 0,
        ]);
        $role->syncPermissions(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function ensureCompanyRoles(int $companyId): void
    {
        $this->ensurePermissions();
        setPermissionsTeamId($companyId);

        foreach ($this->rolePermissions() as $roleName => $permissions) {
            $role = Role::withoutGlobalScopes()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'company_id' => $companyId,
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
            'Company Owner' => ['*'],
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
                'manage-users', 'manage-company',
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
