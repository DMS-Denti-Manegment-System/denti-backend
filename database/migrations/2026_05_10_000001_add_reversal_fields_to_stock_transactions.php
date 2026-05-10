<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transactions', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('transaction_date');
            }

            if (! Schema::hasColumn('stock_transactions', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_transactions', 'reversal_transaction_id')) {
                $table->foreignId('reversal_transaction_id')->nullable()->after('reversed_by')->constrained('stock_transactions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transactions', 'reversal_transaction_id')) {
                $table->dropConstrainedForeignId('reversal_transaction_id');
            }

            if (Schema::hasColumn('stock_transactions', 'reversed_by')) {
                $table->dropConstrainedForeignId('reversed_by');
            }

            if (Schema::hasColumn('stock_transactions', 'reversed_at')) {
                $table->dropColumn('reversed_at');
            }
        });
    }
};
