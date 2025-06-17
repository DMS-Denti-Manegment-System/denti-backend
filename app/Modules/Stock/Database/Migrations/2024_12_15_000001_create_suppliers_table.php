<?php
// app/Modules/Stock/Database/Migrations/2024_12_15_000001_create_suppliers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('additional_info')->nullable();
            $table->timestamps();

            $table->index(['name', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
};