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
        Schema::table('stocks', function (Blueprint $table) {
            // Batch/Parti takibi
            if (! Schema::hasColumn('stocks', 'batch_code')) {
                $table->string('batch_code')->nullable()->after('product_id');
            }

            // Alt birim takibi
            if (! Schema::hasColumn('stocks', 'has_sub_unit')) {
                $table->boolean('has_sub_unit')->default(false)->after('available_stock');
            }
            if (! Schema::hasColumn('stocks', 'sub_unit_name')) {
                $table->string('sub_unit_name')->nullable()->after('has_sub_unit');
            }
            if (! Schema::hasColumn('stocks', 'sub_unit_multiplier')) {
                $table->integer('sub_unit_multiplier')->nullable()->after('sub_unit_name');
            }
            if (! Schema::hasColumn('stocks', 'current_sub_stock')) {
                $table->integer('current_sub_stock')->default(0)->after('sub_unit_multiplier');
            }
            if (! Schema::hasColumn('stocks', 'total_base_units')) {
                $table->integer('total_base_units')->default(0)->after('current_sub_stock');
            }

            // Son kullanma takibi
            if (! Schema::hasColumn('stocks', 'track_expiry')) {
                $table->boolean('track_expiry')->default(true)->after('total_base_units');
            }
            if (! Schema::hasColumn('stocks', 'track_batch')) {
                $table->boolean('track_batch')->default(false)->after('track_expiry');
            }
            if (! Schema::hasColumn('stocks', 'expiry_yellow_days')) {
                $table->integer('expiry_yellow_days')->default(30)->after('track_batch');
            }
            if (! Schema::hasColumn('stocks', 'expiry_red_days')) {
                $table->integer('expiry_red_days')->default(15)->after('expiry_yellow_days');
            }

            // Diğer eksik alanlar
            if (! Schema::hasColumn('stocks', 'storage_location')) {
                $table->string('storage_location')->nullable()->after('expiry_red_days');
            }
            if (! Schema::hasColumn('stocks', 'notes')) {
                $table->text('notes')->nullable()->after('storage_location');
            }
            if (! Schema::hasColumn('stocks', 'reserved_stock')) {
                $table->integer('reserved_stock')->default(0)->after('current_stock');
            }
            if (! Schema::hasColumn('stocks', 'available_stock')) {
                $table->integer('available_stock')->default(0)->after('reserved_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'batch_code', 'has_sub_unit', 'sub_unit_name', 'sub_unit_multiplier',
                'current_sub_stock', 'total_base_units', 'track_expiry', 'track_batch',
                'expiry_yellow_days', 'expiry_red_days', 'storage_location', 'notes',
                'reserved_stock', 'available_stock',
            ]);
        });
    }
};
