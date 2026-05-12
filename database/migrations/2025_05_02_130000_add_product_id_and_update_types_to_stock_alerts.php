<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_alerts', function (Blueprint $table) {
            // Ürün bazlı uyarı sistemi için product_id kolonu (eğer yoksa)
            if (! Schema::hasColumn('stock_alerts', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('stock_id')->constrained()->onDelete('cascade');
            }



            // critical_expiry tipi için enum güncellemesi
            // SQLite'da enum değiştirme desteklenmez, sadece MySQL/MariaDB için
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->enum('type', [
                    'low_stock',
                    'critical_stock',
                    'expired',
                    'near_expiry',
                    'critical_expiry',
                ])->change();
            }

            // Yeni index'ler (eğer yoksa)
            if (! Schema::hasIndex('stock_alerts', 'idx_stock_alerts_product_active')) {
                $table->index(['product_id', 'is_active'], 'idx_stock_alerts_product_active');
            }

        });
    }

    public function down()
    {
        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            $table->dropColumn('company_id');

            $table->enum('type', [
                'low_stock',
                'critical_stock',
                'expired',
                'near_expiry',
            ])->change();

            $table->dropIndex('idx_stock_alerts_product_active');
            $table->dropIndex('idx_stock_alerts_company_active');
        });
    }
};
