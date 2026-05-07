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
            $table->string('code')->unique()->nullable()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('email')->nullable()->change();

            $table->unique(['company_id', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'username']);
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->change();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
