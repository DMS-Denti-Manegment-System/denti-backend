<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skipped because we are removing multi-tenant structures
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::transaction(function () {
            if ($this->indexExists('roles', 'roles_company_id_name_guard_name_unique')) {
                DB::statement('ALTER TABLE roles DROP INDEX roles_company_id_name_guard_name_unique');
            }

            if (! $this->indexExists('roles', 'roles_name_guard_name_unique')) {
                DB::statement('ALTER TABLE roles ADD UNIQUE roles_name_guard_name_unique (name, guard_name)');
            }
        });
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
