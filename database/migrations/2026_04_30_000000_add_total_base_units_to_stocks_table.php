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
        Schema::table('stocks', function (Blueprint $table) {
            // Virtual generated column for performance
            $expression = 'CASE 
                WHEN has_sub_unit = 1 THEN (current_stock * COALESCE(sub_unit_multiplier, 1)) + current_sub_stock 
                ELSE current_stock 
            END';

            $table->integer('total_base_units_virtual')->virtualAs($expression)->after('current_sub_stock');
            $table->index('total_base_units_virtual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('total_base_units_virtual');
        });
    }
};
