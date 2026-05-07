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
            $table->boolean('has_expiration_date')->default(false)->after('is_active');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transactions', 'notes')) {
                $table->string('notes')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_expiration_date');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
