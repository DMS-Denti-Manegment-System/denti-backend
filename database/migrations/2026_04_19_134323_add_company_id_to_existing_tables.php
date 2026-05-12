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
        // Deprecated - company_id no longer needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'stocks',
            'clinics',
            'suppliers',
            'stock_requests',
            'stock_transactions',
            'stock_alerts',
            'categories',
            'todos',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
