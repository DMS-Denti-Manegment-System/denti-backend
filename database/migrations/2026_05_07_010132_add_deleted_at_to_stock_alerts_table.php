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
        if (Schema::hasColumn('stock_alerts', 'deleted_at')) {
            return;
        }

        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('stock_alerts', 'deleted_at')) {
            return;
        }

        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
