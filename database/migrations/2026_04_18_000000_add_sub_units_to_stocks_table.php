<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->boolean('has_sub_unit')->default(false)->after('unit');
            $table->string('sub_unit_name')->nullable()->after('has_sub_unit');
            $table->integer('sub_unit_multiplier')->nullable()->after('sub_unit_name');
            $table->integer('current_sub_stock')->default(0)->after('current_stock');
        });
    }

    public function down()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'has_sub_unit',
                'sub_unit_name',
                'sub_unit_multiplier',
                'current_sub_stock',
            ]);
        });
    }
};
