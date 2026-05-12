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
        Schema::disableForeignKeyConstraints();

        // 2. Drop company_id from all operational tables
        $tables = [
            'clinics', 'users', 'products', 'stocks', 'stock_transactions',
            'stock_requests', 'stock_transfers', 'stock_alerts', 'suppliers',
            'categories', 'todos',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    // List of potential indexes that might contain company_id
                    $indexes = [
                        'company_id_index',
                        'company_id_foreign',
                        'company_id_unique',
                        'idx_stocks_company_clinic_product',
                        'idx_stocks_company_active_expiry',
                        'idx_transactions_company_clinic_created',
                        'idx_users_company_clinic_active',
                        'users_company_id_username_unique',
                        'stock_transfers_company_id_status_index',
                        'idx_stock_alerts_company_active',
                        'idx_alerts_company_active_clinic',
                        'products_name_company_id_index',
                        'idx_products_company_name',
                        'idx_products_company_sku',
                        $table . '_company_id_index',
                        $table . '_company_id_foreign',
                        $table . '_company_id_unique'
                    ];

                    foreach ($indexes as $index) {
                        try {
                            $blueprint->dropUnique($index);
                        } catch (\Exception $e) {}
                        try {
                            $blueprint->dropForeign($index);
                        } catch (\Exception $e) {}
                        try {
                            $blueprint->dropIndex($index);
                        } catch (\Exception $e) {}
                    }

                    // Try to drop by array if named drop failed
                    try {
                        $blueprint->dropForeign(['company_id']);
                    } catch (\Exception $e) {}

                    $blueprint->dropColumn('company_id');
                });
            }
        }

        // 3. Drop company_id from Spatie permission tables
        if (Schema::hasColumn('roles', 'company_id')) {
            try {
                \Illuminate\Support\Facades\DB::statement('DROP INDEX roles_team_foreign_key_index');
            } catch (\Exception $e) {
            }
            try {
                \Illuminate\Support\Facades\DB::statement('DROP INDEX roles_company_id_name_guard_name_unique');
            } catch (\Exception $e) {
            }
            Schema::table('roles', function (Blueprint $tableBlueprint) {
                $tableBlueprint->dropColumn('company_id');
            });
        }

        // For pivot tables with composite primary keys including company_id,
        // it's safest to recreate them in SQLite
        if (Schema::hasColumn('model_has_roles', 'company_id')) {
            Schema::dropIfExists('model_has_roles');
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        if (Schema::hasColumn('model_has_permissions', 'company_id')) {
            Schema::dropIfExists('model_has_permissions');
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        // 4. Drop standalone tenant tables
        Schema::dropIfExists('user_invitations');
        Schema::dropIfExists('companies');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // This migration is destructive and not easily reversible.
    }
};
