<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE stock_transactions MODIFY type ENUM(
            'purchase', 'usage', 'transfer_in', 'transfer_out', 'adjustment',
            'adjustment_plus', 'adjustment_minus', 'adjustment_increase', 'adjustment_decrease',
            'entry', 'loss', 'expired', 'damaged', 'returned', 'return_in', 'return_out'
        ) NOT NULL");

        DB::statement("ALTER TABLE stock_requests MODIFY status ENUM(
            'pending', 'approved', 'rejected', 'in_transit', 'completed', 'cancelled'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE stock_transactions MODIFY type ENUM(
            'purchase', 'usage', 'transfer_in', 'transfer_out', 'adjustment',
            'expired', 'damaged', 'returned'
        ) NOT NULL");

        DB::statement("ALTER TABLE stock_requests MODIFY status ENUM(
            'pending', 'approved', 'rejected', 'completed', 'cancelled'
        ) NOT NULL DEFAULT 'pending'");
    }
};
