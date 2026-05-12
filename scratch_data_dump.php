<?php

use App\Models\Product;
use App\Models\Stock;
use App\Services\StockService;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- ALL PRODUCTS ---\n";
$products = Product::all();
foreach ($products as $p) {
    echo "ID: {$p->id}, Name: {$p->name}, Clinic: {$p->clinic_id}, Red: {$p->red_alert_level}, Yellow: {$p->yellow_alert_level}, ShowZeroCrit: ".var_export($p->show_zero_stock_in_critical, true)."\n";
}

echo "\n--- ALL STOCKS ---\n";
$stocks = Stock::all();
foreach ($stocks as $s) {
    echo "ID: {$s->id}, Product: {$s->product_id}, Clinic: {$s->clinic_id}, Stock: {$s->current_stock}, Active: ".var_export($s->is_active, true)."\n";
}

echo "\n--- AGGREGATED STATUS PER PRODUCT ---\n";
foreach ($products as $p) {
    echo "Product: {$p->name} (ID: {$p->id})\n";
    echo '  Total Stock (all clinics): '.$p->total_stock."\n";
    echo '  Status (all clinics): '.$p->stock_status."\n";
}

$service = app(StockService::class);
echo "\n--- STATS FOR CLINIC 1 ---\n";
print_r($service->getStockStats(1));

echo "\n--- STATS FOR CLINIC 2 ---\n";
print_r($service->getStockStats(2));

echo "\n--- STATS GLOBAL (NULL) ---\n";
print_r($service->getStockStats(null));
