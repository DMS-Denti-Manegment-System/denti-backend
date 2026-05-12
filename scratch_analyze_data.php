<?php

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- ANALYZING PRODUCT 1 (lllewrwerwerrrrr) ---\n";
$p1 = Product::find(1);
echo "Total Stock: " . $p1->total_stock . "\n";
echo "Red Level: " . ($p1->red_alert_level ?? $p1->critical_stock_level ?? 5) . "\n";
echo "Yellow Level: " . ($p1->yellow_alert_level ?? $p1->min_stock_level ?? 10) . "\n";
echo "Show Zero in Critical: " . ($p1->show_zero_stock_in_critical === null ? 'NULL' : ($p1->show_zero_stock_in_critical ? 'TRUE' : 'FALSE')) . "\n";
echo "Status Attr: " . $p1->stock_status . "\n";

echo "\n--- ANALYZING SCOPES ---\n";
$low = Stock::lowStock()->get();
echo "Low Stock Count: " . $low->count() . "\n";
foreach ($low as $s) {
    echo "  - Stock ID: " . $s->id . " (Product: " . $s->product->name . ", Qty: " . $s->current_stock . ")\n";
}

$crit = Stock::criticalStock()->get();
echo "Critical Stock Count: " . $crit->count() . "\n";
foreach ($crit as $s) {
    echo "  - Stock ID: " . $s->id . " (Product: " . $s->product->name . ", Qty: " . $s->current_stock . ")\n";
}

echo "\n--- RAW QUERY CHECK FOR CRITICAL ---\n";
$totalUnitsRaw = Stock::totalBaseUnitsRaw();
$rawCrit = DB::table('stocks')
    ->join('products', 'stocks.product_id', '=', 'products.id')
    ->whereNull('stocks.deleted_at')
    ->whereNull('products.deleted_at')
    ->whereRaw($totalUnitsRaw . ' <= COALESCE(products.red_alert_level, products.critical_stock_level, 5)')
    ->get();
echo "Raw Critical Count: " . $rawCrit->count() . "\n";
foreach ($rawCrit as $s) {
    echo "  - Stock ID: " . $s->id . " (Product: " . $s->name . ", Total Units: " . $s->current_stock . ")\n";
}
