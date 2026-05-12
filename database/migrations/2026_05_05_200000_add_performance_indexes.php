<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stocks tablosu - en çok kullanılan sorgular için composite index
        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['clinic_id', 'product_id'], 'idx_stocks_clinic_product');
            $table->index(['is_active', 'expiry_date'], 'idx_stocks_active_expiry');
        });

        // Stock transactions - stock_id + tarih sıralaması için
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->index(['stock_id', 'created_at'], 'idx_transactions_stock_created');
            $table->index(['clinic_id', 'created_at'], 'idx_transactions_clinic_created');
        });

        // Stock alerts - aktif uyarılar için
        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->index(['is_active', 'clinic_id'], 'idx_alerts_active_clinic');
            $table->index(['product_id', 'is_active'], 'idx_alerts_product_active');
        });

        // Products - SKU aramaları için
        Schema::table('products', function (Blueprint $table) {
            $table->index(['sku'], 'idx_products_sku');
            $table->index(['name'], 'idx_products_name');
        });

        // Users - giriş ve yetki kontrolleri için
        Schema::table('users', function (Blueprint $table) {
            $table->index(['clinic_id', 'is_active'], 'idx_users_clinic_active');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('idx_stocks_clinic_product');
            $table->dropIndex('idx_stocks_active_expiry');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_stock_created');
            $table->dropIndex('idx_transactions_clinic_created');
        });

        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_active_clinic');
            $table->dropIndex('idx_alerts_product_active');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_sku');
            $table->dropIndex('idx_products_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_clinic_active');
        });
    }
};
