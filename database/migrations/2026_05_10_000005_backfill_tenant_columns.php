<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('UPDATE stocks s JOIN clinics c ON c.id = s.clinic_id SET s.company_id = c.company_id WHERE s.company_id IS NULL');
            DB::statement('UPDATE stock_transactions t JOIN stocks s ON s.id = t.stock_id SET t.company_id = s.company_id WHERE t.company_id IS NULL');
            DB::statement('UPDATE stock_alerts a JOIN stocks s ON s.id = a.stock_id SET a.company_id = s.company_id WHERE a.company_id IS NULL');
            DB::statement('UPDATE stock_requests r JOIN stocks s ON s.id = r.stock_id SET r.company_id = s.company_id WHERE r.company_id IS NULL');

            return;
        }

        DB::statement('UPDATE stocks SET company_id = (SELECT company_id FROM clinics WHERE clinics.id = stocks.clinic_id) WHERE company_id IS NULL');
        DB::statement('UPDATE stock_transactions SET company_id = (SELECT company_id FROM stocks WHERE stocks.id = stock_transactions.stock_id) WHERE company_id IS NULL');
        DB::statement('UPDATE stock_alerts SET company_id = (SELECT company_id FROM stocks WHERE stocks.id = stock_alerts.stock_id) WHERE company_id IS NULL');
        DB::statement('UPDATE stock_requests SET company_id = (SELECT company_id FROM stocks WHERE stocks.id = stock_requests.stock_id) WHERE company_id IS NULL');
    }

    public function down(): void
    {
        //
    }
};
