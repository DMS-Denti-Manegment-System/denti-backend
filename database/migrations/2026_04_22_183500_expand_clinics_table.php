<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->string('city')->nullable()->after('address');
            $table->string('district')->nullable()->after('city');
            $table->string('manager_name')->nullable()->after('responsible_person');
            $table->string('postal_code')->nullable()->after('district');
            $table->string('website')->nullable()->after('postal_code');
            $table->string('opening_hours')->nullable()->after('website');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });
    }

    public function down()
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'email', 'address', 'city', 'district',
                'manager_name', 'postal_code', 'website',
                'opening_hours', 'latitude', 'longitude',
            ]);
        });
    }
};
