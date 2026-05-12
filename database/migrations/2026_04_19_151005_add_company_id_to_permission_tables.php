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
        // Deprecated - Permission teams (companies) no longer used
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
