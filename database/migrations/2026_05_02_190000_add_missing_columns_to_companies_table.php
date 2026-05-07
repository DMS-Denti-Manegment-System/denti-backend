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
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'address')) {
                $table->text('address')->nullable()->after('domain');
            }

            if (! Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }

            if (! Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('companies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'email', 'is_active']);
        });
    }
};
