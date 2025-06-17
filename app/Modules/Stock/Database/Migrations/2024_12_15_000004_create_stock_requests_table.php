<?php
// app/Modules/Stock/Database/Migrations/2024_12_15_000004_create_stock_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();

            // Talep eden ve talep edilen klinik
            $table->foreignId('requester_clinic_id')->constrained('clinics')->onDelete('restrict');
            $table->foreignId('requested_from_clinic_id')->constrained('clinics')->onDelete('restrict');

            // Talep edilen stok
            $table->foreignId('stock_id')->constrained()->onDelete('restrict');
            $table->integer('requested_quantity');
            $table->integer('approved_quantity')->nullable();

            // Durum
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])
                  ->default('pending');

            // Açıklamalar
            $table->text('request_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Tarihler
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Kullanıcı bilgileri
            $table->string('requested_by');
            $table->string('approved_by')->nullable();

            $table->timestamps();

            $table->index(['requester_clinic_id', 'status']);
            $table->index(['requested_from_clinic_id', 'status']);
            $table->index(['stock_id']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_requests');
    }
};