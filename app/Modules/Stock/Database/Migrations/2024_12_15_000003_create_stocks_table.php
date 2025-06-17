<?php
// app/Modules/Stock/Database/Migrations/2024_12_15_000003_create_stocks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('unit');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();

            // Tedarikçi bilgileri
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Stok seviyeleri
            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->integer('available_stock')->default(0);
            $table->integer('min_stock_level')->default(10);
            $table->integer('critical_stock_level')->default(5);

            // Alarm seviyeleri
            $table->integer('yellow_alert_level')->default(10);
            $table->integer('red_alert_level')->default(5);

            // İç kullanım
            $table->integer('internal_usage_count')->default(0);

            // Durum
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->boolean('track_expiry')->default(true);
            $table->boolean('track_batch')->default(false);

            // Konum bilgileri
            $table->foreignId('clinic_id')->constrained()->onDelete('restrict');
            $table->string('storage_location')->nullable();

            $table->timestamps();

            // İndeksler
            $table->index(['name', 'status']);
            $table->index(['clinic_id', 'status']);
            $table->index(['supplier_id']);
            $table->index(['expiry_date']);
            $table->index(['current_stock', 'min_stock_level']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
};