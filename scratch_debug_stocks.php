<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Stock;
use App\Models\Clinic;

$totalStocks = Stock::count();
$activeStocks = Stock::where('is_active', true)->count();
$clinicsWithStock = Stock::distinct()->pluck('clinic_id');

echo "Total Stocks: $totalStocks\n";
echo "Active Stocks: $activeStocks\n";
echo "Clinics with Stock IDs: " . implode(', ', $clinicsWithStock->toArray()) . "\n";

foreach ($clinicsWithStock as $cid) {
    $clinicName = Clinic::find($cid)?->name ?? 'Unknown';
    $stocks = Stock::where('clinic_id', $cid)->get();
    echo "Clinic: $clinicName (ID: $cid) has " . $stocks->count() . " stocks total.\n";
    foreach ($stocks as $s) {
        echo "  - Stock ID: {$s->id}, Product ID: {$s->product_id}, is_active: " . ($s->is_active ? 'TRUE' : 'FALSE') . ", status: '{$s->status}'\n";
    }
}
