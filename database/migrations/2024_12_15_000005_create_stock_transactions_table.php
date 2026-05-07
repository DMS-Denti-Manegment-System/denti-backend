<?php

// app/Modules/Stock/Database/Migrations/2024_12_15_000005_create_stock_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();

            $table->foreignId('stock_id')->constrained()->onDelete('cascade');
            $table->foreignId('clinic_id')->constrained()->onDelete('restrict');

            // İşlem tipi
            $table->enum('type', [
                'purchase',     // Satın alma
                'usage',        // Kullanım
                'transfer_in',  // Transfer giriş
                'transfer_out', // Transfer çıkış
                'adjustment',   // Düzeltme
                'expired',      // Son kullanma tarihi geçen
                'damaged',      // Hasarlı
                'returned',      // İade
            ]);

            // Miktarlar
            $table->integer('quantity');
            $table->integer('previous_stock');
            $table->integer('new_stock');

            // Fiyat bilgileri
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();

            // Referans bilgileri
            $table->foreignId('stock_request_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reference_number')->nullable();
            $table->string('batch_number')->nullable();

            // Açıklama
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Kullanıcı bilgileri
            $table->string('performed_by');
            $table->timestamp('transaction_date');

            $table->timestamps();

            $table->index(['stock_id', 'type']);
            $table->index(['clinic_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_transactions');
    }
};
