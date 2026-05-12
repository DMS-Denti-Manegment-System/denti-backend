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
                // Drop indexes manually so we can catch exceptions immediately
                $indexesToDrop = [
                    $table.'_company_id_index',
                    $table.'_company_id_foreign',
                ];

                if ($table === 'stocks') {
                    $indexesToDrop[] = 'idx_stocks_company_clinic_product';
                    $indexesToDrop[] = 'idx_stocks_company_active_expiry';
                }
                if ($table === 'stock_transactions') {
                    $indexesToDrop[] = 'idx_transactions_company_clinic_created';
                }
                if ($table === 'users') {
                    $indexesToDrop[] = 'idx_users_company_clinic_active';
                    $indexesToDrop[] = 'users_company_id_username_unique';
                }
                if ($table === 'stock_transfers') {
                    $indexesToDrop[] = 'stock_transfers_company_id_status_index';
                }
                if ($table === 'stock_alerts') {
                    $indexesToDrop[] = 'idx_stock_alerts_company_active';
                    $indexesToDrop[] = 'idx_alerts_company_active_clinic';
                }
                if ($table === 'products') {
                    $indexesToDrop[] = 'products_name_company_id_index';
                    $indexesToDrop[] = 'idx_products_company_name';
                    $indexesToDrop[] = 'idx_products_company_sku';
                }

                foreach ($indexesToDrop as $index) {
                    try {
                        \Illuminate\Support\Facades\DB::statement("DROP INDEX {$index}");
                    } catch (\Exception $e) {
                    }
                }

                // 1. Drop foreign key first (rebuilds table in SQLite)
                try {
                    Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                        $tableBlueprint->dropForeign([$table.'_company_id_foreign']);
                    });
                } catch (\Exception $e) {
                    // Ignore if no foreign key
                }
                try {
                    Schema::table($table, function (Blueprint $tableBlueprint) {
                        $tableBlueprint->dropForeign(['company_id']);
                    });
                } catch (\Exception $e) {
                    // Ignore if no foreign key
                }

                // 2. Drop the column
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropColumn('company_id');
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
