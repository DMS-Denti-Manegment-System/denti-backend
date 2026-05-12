<?php

use App\Models\Stock;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$clinicId = 2;
$totalUnitsRaw = Stock::totalBaseUnitsRaw();

$stockSummaryQuery = DB::table('stocks')
    ->select('product_id')
    ->selectRaw("SUM({$totalUnitsRaw}) as total_stock")
    ->whereNull('deleted_at')
    ->where('is_active', true);

if ($clinicId) {
    $stockSummaryQuery->where('clinic_id', $clinicId);
}

$statsQuery = DB::table('products')
    ->leftJoinSub($stockSummaryQuery, 'stock_summary', 'products.id', '=', 'stock_summary.product_id')
    ->whereNull('products.deleted_at')
    ->where('products.is_active', true);

if ($clinicId) {
    $statsQuery->where(function ($q) use ($clinicId) {
        $q->where('products.clinic_id', $clinicId)
            ->orWhereNotNull('stock_summary.product_id');
    });
}

echo "--- INDIVIDUAL PRODUCT STATUSES ---\n";
$items = $statsQuery->select([
    'products.id',
    'products.name',
    'products.show_zero_stock_in_critical',
    DB::raw('COALESCE(stock_summary.total_stock, 0) as total_stock'),
])->get();

foreach ($items as $item) {
    $total = $item->total_stock;
    $red = 5;
    $showZero = $item->show_zero_stock_in_critical;

    $isCrit = ($total <= $red) && ! ($total == 0 && $showZero == 0);
    echo "ID: {$item->id}, Name: {$item->name}, Total: {$total}, ShowZero: {$showZero}, IsCrit: ".($isCrit ? 'YES' : 'NO')."\n";
}
