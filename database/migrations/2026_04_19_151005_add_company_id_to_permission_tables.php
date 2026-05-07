<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamKey = $columnNames['team_foreign_key'] ?? 'company_id';

        // 1. Roles table
        if (! Schema::hasColumn($tableNames['roles'], $teamKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
                $table->unsignedBigInteger($teamKey)->after('id')->nullable();
                $table->index($teamKey);

                // Unique constraint change is hard in migration for some DBs,
                // but we should at least have the column.
            });
        }

        // 2. Model Has Permissions
        if (! Schema::hasColumn($tableNames['model_has_permissions'], $teamKey)) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamKey) {
                $table->unsignedBigInteger($teamKey)->nullable(); // Nullable for compatibility
                $table->index($teamKey);
            });
        }

        // 3. Model Has Roles
        if (! Schema::hasColumn($tableNames['model_has_roles'], $teamKey)) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamKey) {
                $table->unsignedBigInteger($teamKey)->nullable(); // Nullable for compatibility
                $table->index($teamKey);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamKey = $columnNames['team_foreign_key'] ?? 'company_id';

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
            $table->dropColumn($teamKey);
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamKey) {
            $table->dropColumn($teamKey);
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamKey) {
            $table->dropColumn($teamKey);
        });
    }
};
