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
            if (! Schema::hasColumn('products', 'has_sub_unit')) {
                $table->boolean('has_sub_unit')->default(false)->after('has_expiration_date');
            }
            if (! Schema::hasColumn('products', 'sub_unit_name')) {
                $table->string('sub_unit_name')->nullable()->after('has_sub_unit');
            }
            if (! Schema::hasColumn('products', 'sub_unit_multiplier')) {
                $table->integer('sub_unit_multiplier')->nullable()->after('sub_unit_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['has_sub_unit', 'sub_unit_name', 'sub_unit_multiplier']);
        });
    }
};
