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
        if (!Schema::hasColumn('stocks', 'deleted_at')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('stock_transactions', 'deleted_at')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('clinics', 'deleted_at')) {
            Schema::table('clinics', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
