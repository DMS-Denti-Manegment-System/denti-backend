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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            // İlişkiler
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade');

            $table->foreignId('stock_id')
                ->constrained('stocks')
                ->onDelete('cascade');

            $table->foreignId('from_clinic_id')
                ->constrained('clinics')
                ->onDelete('cascade');

            $table->foreignId('to_clinic_id')
                ->constrained('clinics')
                ->onDelete('cascade');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            // Transfer detayları
            $table->integer('quantity');
            $table->text('notes')->nullable();

            // Durum
            $table->enum('status', [
                'pending',      // Beklemede (onay bekliyor)
                'approved',     // Onaylandı
                'in_transit',   // Transfer sürecinde
                'completed',    // Tamamlandı
                'rejected',     // Reddedildi
                'cancelled',     // İptal edildi
            ])->default('pending');

            // Kullanıcı ilişkileri
            $table->foreignId('requested_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Zamanlar
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Reddetme sebebi
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // İndeksler
            $table->index(['company_id', 'status']);
            $table->index(['from_clinic_id', 'status']);
            $table->index(['to_clinic_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index('requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
