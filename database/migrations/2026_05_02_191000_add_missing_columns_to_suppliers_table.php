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
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'website')) {
                $table->string('website')->nullable()->after('tax_number');
            }

            if (! Schema::hasColumn('suppliers', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('website');
            }

            if (! Schema::hasColumn('suppliers', 'notes')) {
                $table->text('notes')->nullable()->after('payment_terms');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['website', 'payment_terms', 'notes']);
        });
    }
};
