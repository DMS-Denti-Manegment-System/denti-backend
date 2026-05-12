<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceUseStockTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_main_unit_for_sub_unit_enabled_stock_when_is_sub_unit_is_false(): void
    {
        $stock = $this->createSubUnitStock(currentStock: 5, currentSubStock: 3, multiplier: 10);

        app(StockService::class)->useStock(
            stockId: $stock->id,
            quantity: 2,
            performedBy: 'Test User',
            isSubUnit: false
        );

        $stock->refresh();
        $this->assertSame(3, $stock->current_stock);
        $this->assertSame(3, $stock->current_sub_stock);

        $transaction = StockTransaction::query()
            ->where('stock_id', $stock->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertFalse((bool) $transaction->is_sub_unit);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_sub_unit_calculation_when_is_sub_unit_is_true(): void
    {
        $stock = $this->createSubUnitStock(currentStock: 5, currentSubStock: 3, multiplier: 10);

        app(StockService::class)->useStock(
            stockId: $stock->id,
            quantity: 13,
            performedBy: 'Test User',
            isSubUnit: true
        );

        $stock->refresh();
        $this->assertSame(4, $stock->current_stock);
        $this->assertSame(0, $stock->current_sub_stock);

        $transaction = StockTransaction::query()
            ->where('stock_id', $stock->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertTrue((bool) $transaction->is_sub_unit);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_zero_stock_critical_visibility_when_stock_becomes_zero(): void
    {
        $clinic = Clinic::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'clinic_id' => $clinic->id,
            'show_zero_stock_in_critical' => true,
            'yellow_alert_level' => 10,
            'red_alert_level' => 5,
            'min_stock_level' => 10,
            'critical_stock_level' => 5,
        ]);

        $stock = Stock::create([
            'product_id' => $product->id,
            'clinic_id' => $clinic->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 1,
            'current_sub_stock' => 0,
            'reserved_stock' => 0,
            'available_stock' => 1,
            'has_sub_unit' => false,
            'is_active' => true,
            'track_expiry' => false,
            'currency' => 'TRY',
        ]);

        app(StockService::class)->useStock(
            stockId: $stock->id,
            quantity: 1,
            performedBy: 'Test User',
            isSubUnit: false,
            showZeroStockInCritical: false
        );

        $product->refresh();
        $this->assertFalse((bool) $product->show_zero_stock_in_critical);
        $this->assertSame('low_stock', $product->stock_status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_update_zero_stock_visibility_when_only_one_batch_is_depleted(): void
    {
        $clinic = Clinic::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'clinic_id' => $clinic->id,
            'show_zero_stock_in_critical' => true,
        ]);

        $depletedBatch = Stock::create([
            'product_id' => $product->id,
            'clinic_id' => $clinic->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 1,
            'current_sub_stock' => 0,
            'reserved_stock' => 0,
            'available_stock' => 1,
            'has_sub_unit' => false,
            'is_active' => true,
            'track_expiry' => true,
            'currency' => 'TRY',
            'expiry_date' => now()->addDays(10)->toDateString(),
        ]);

        Stock::create([
            'product_id' => $product->id,
            'clinic_id' => $clinic->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 3,
            'current_sub_stock' => 0,
            'reserved_stock' => 0,
            'available_stock' => 3,
            'has_sub_unit' => false,
            'is_active' => true,
            'track_expiry' => true,
            'currency' => 'TRY',
            'expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        app(StockService::class)->useStock(
            stockId: $depletedBatch->id,
            quantity: 1,
            performedBy: 'Test User',
            isSubUnit: false,
            showZeroStockInCritical: false
        );

        $product->refresh();
        $this->assertTrue((bool) $product->show_zero_stock_in_critical);
        $this->assertGreaterThan(0, $product->total_stock);
    }

    private function createSubUnitStock(int $currentStock, int $currentSubStock, int $multiplier): Stock
    {
        $clinic = Clinic::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'clinic_id' => $clinic->id,
            'has_sub_unit' => true,
            'sub_unit_name' => 'Adet',
            'sub_unit_multiplier' => $multiplier,
        ]);

        return Stock::create([
            'product_id' => $product->id,
            'clinic_id' => $clinic->id,
            'supplier_id' => $supplier->id,
            'current_stock' => $currentStock,
            'current_sub_stock' => $currentSubStock,
            'reserved_stock' => 0,
            'available_stock' => $currentStock,
            'has_sub_unit' => true,
            'sub_unit_name' => 'Adet',
            'sub_unit_multiplier' => $multiplier,
            'is_active' => true,
            'track_expiry' => false,
            'currency' => 'TRY',
        ]);
    }
}
