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
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'show_zero_stock_in_critical')) {
                $table->boolean('show_zero_stock_in_critical')
                    ->default(true)
                    ->after('sub_unit_multiplier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'show_zero_stock_in_critical')) {
                $table->dropColumn('show_zero_stock_in_critical');
            }
        });
    }
};
