<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::transaction(function () {
            DB::table('roles')->whereNull('company_id')->update(['company_id' => 0]);
            if ($this->indexExists('roles', 'roles_name_guard_name_unique')) {
                DB::statement('ALTER TABLE roles DROP INDEX roles_name_guard_name_unique');
            }

            if (! $this->indexExists('roles', 'roles_company_id_name_guard_name_unique')) {
                DB::statement('ALTER TABLE roles ADD UNIQUE roles_company_id_name_guard_name_unique (company_id, name, guard_name)');
            }

            $this->provisionCompanyRoles();
            $this->moveUserRoleAssignmentsToUserCompanies();
            $this->moveUserPermissionAssignmentsToUserCompanies();

            $this->rebuildPrimaryKey(
                'model_has_permissions',
                'BIGINT UNSIGNED NOT NULL',
                'company_id, permission_id, model_id, model_type'
            );

            $this->rebuildPrimaryKey(
                'model_has_roles',
                'BIGINT UNSIGNED NOT NULL',
                'company_id, role_id, model_id, model_type'
            );
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::transaction(function () {
            $this->rebuildPrimaryKey(
                'model_has_roles',
                'BIGINT UNSIGNED NULL',
                'role_id, model_id, model_type'
            );

            $this->rebuildPrimaryKey(
                'model_has_permissions',
                'BIGINT UNSIGNED NULL',
                'permission_id, model_id, model_type'
            );

            if ($this->indexExists('roles', 'roles_company_id_name_guard_name_unique')) {
                DB::statement('ALTER TABLE roles DROP INDEX roles_company_id_name_guard_name_unique');
            }

            if (! $this->indexExists('roles', 'roles_name_guard_name_unique')) {
                DB::statement('ALTER TABLE roles ADD UNIQUE roles_name_guard_name_unique (name, guard_name)');
            }
        });
    }

    private function provisionCompanyRoles(): void
    {
        $sourceRoles = DB::table('roles')
            ->where('company_id', 0)
            ->where('name', '!=', 'Super Admin')
            ->get();

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            foreach ($sourceRoles as $sourceRole) {
                DB::table('roles')->insertOrIgnore([
                    'company_id' => $companyId,
                    'name' => $sourceRole->name,
                    'guard_name' => $sourceRole->guard_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $targetRole = DB::table('roles')
                    ->where('company_id', $companyId)
                    ->where('name', $sourceRole->name)
                    ->where('guard_name', $sourceRole->guard_name)
                    ->first();

                if (! $targetRole) {
                    continue;
                }

                $permissionIds = DB::table('role_has_permissions')
                    ->where('role_id', $sourceRole->id)
                    ->pluck('permission_id');

                foreach ($permissionIds as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionId,
                        'role_id' => $targetRole->id,
                    ]);
                }
            }
        }
    }

    private function moveUserRoleAssignmentsToUserCompanies(): void
    {
        DB::statement("
            UPDATE model_has_roles m
            JOIN users u ON u.id = m.model_id AND m.model_type = 'App\\\\Models\\\\User'
            JOIN roles old_roles ON old_roles.id = m.role_id
            JOIN roles new_roles
                ON new_roles.name = old_roles.name
                AND new_roles.guard_name = old_roles.guard_name
                AND new_roles.company_id = COALESCE(u.company_id, 0)
            SET m.role_id = new_roles.id,
                m.company_id = COALESCE(u.company_id, 0)
        ");

        DB::table('model_has_roles')->whereNull('company_id')->update(['company_id' => 0]);
    }

    private function moveUserPermissionAssignmentsToUserCompanies(): void
    {
        DB::statement("
            UPDATE model_has_permissions m
            JOIN users u ON u.id = m.model_id AND m.model_type = 'App\\\\Models\\\\User'
            SET m.company_id = COALESCE(u.company_id, 0)
        ");

        DB::table('model_has_permissions')->whereNull('company_id')->update(['company_id' => 0]);
    }

    private function rebuildPrimaryKey(string $table, string $companyColumnDefinition, string $columns): void
    {
        DB::statement("ALTER TABLE {$table} DROP PRIMARY KEY");
        DB::statement("ALTER TABLE {$table} MODIFY company_id {$companyColumnDefinition}");
        DB::statement("ALTER TABLE {$table} ADD PRIMARY KEY ({$columns})");
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
