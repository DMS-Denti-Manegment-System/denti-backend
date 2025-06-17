<?php
// app/Modules/Stock/Database/Migrations/2024_12_15_000006_create_stock_alerts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_id')->constrained()->onDelete('cascade');
            $table->foreignId('clinic_id')->constrained()->onDelete('cascade');

            // Alarm tipi
            $table->enum('type', [
                'low_stock',      // Düşük stok (sarı)
                'critical_stock', // Kritik stok (kırmızı)
                'expired',        // Süresi geçen
                'near_expiry'     // Süresi yaklaşan
            ]);

            // Alarm bilgileri
            $table->string('title');
            $table->text('message');
            $table->integer('current_stock_level')->nullable();
            $table->integer('threshold_level')->nullable();
            $table->date('expiry_date')->nullable();

            // Durum
            $table->boolean('is_active')->default(true);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by')->nullable();

            $table->timestamps();

            $table->index(['stock_id', 'type', 'is_active']);
            $table->index(['clinic_id', 'is_active']);
            $table->index(['type', 'is_resolved']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_alerts');
    }
};