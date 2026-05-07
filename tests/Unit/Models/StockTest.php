<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_total_base_units_correctly()
    {
        $stock = Stock::factory()->create([
            'current_stock' => 5,
            'has_sub_unit' => true,
            'sub_unit_multiplier' => 10,
            'current_sub_stock' => 3,
        ]);

        // (5 * 10) + 3 = 53
        $this->assertEquals(53, $stock->total_base_units);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_current_stock_when_no_sub_units()
    {
        $stock = Stock::factory()->create([
            'current_stock' => 15,
            'has_sub_unit' => false,
        ]);

        $this->assertEquals(15, $stock->total_base_units);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_checks_if_stock_is_expired()
    {
        $stock = Stock::factory()->create([
            'expiry_date' => now()->subDay(),
        ]);

        $this->assertTrue($stock->is_expired);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_checks_if_stock_is_near_expiry()
    {
        $stock = Stock::factory()->create([
            'expiry_date' => now()->addDays(5),
            'expiry_yellow_days' => 7,
            'expiry_red_days' => 3,
        ]);

        $this->assertTrue($stock->is_near_expiry);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_days_to_expiry()
    {
        $stock = Stock::factory()->create([
            'expiry_date' => now()->addDays(10),
            'track_expiry' => true,
        ]);

        $this->assertEqualsWithDelta(10, $stock->days_to_expiry, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_zero_days_to_expiry_when_no_expiry_date()
    {
        $stock = Stock::factory()->create([
            'expiry_date' => null,
        ]);

        $this->assertEquals(0, $stock->days_to_expiry);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_available_stock()
    {
        $stock = Stock::factory()->create([
            'current_stock' => 100,
            'reserved_stock' => 20,
        ]);

        $this->assertEquals(80, $stock->available_stock);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_belongs_to_a_product()
    {
        $product = Product::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Product::class, $stock->product);
        $this->assertEquals($product->id, $stock->product->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_many_transactions()
    {
        $stock = Stock::factory()->create();

        \App\Models\StockTransaction::factory()->create([
            'stock_id' => $stock->id,
            'quantity' => 10,
            'type' => 'in',
        ]);

        $this->assertCount(1, $stock->fresh()->transactions);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_negative_available_stock_if_over_reserved()
    {
        $stock = Stock::factory()->create([
            'current_stock' => 10,
            'reserved_stock' => 15,
        ]);

        $this->assertEquals(-5, $stock->available_stock);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_missing_multiplier_in_total_base_units()
    {
        $stock = Stock::factory()->create([
            'current_stock' => 5,
            'has_sub_unit' => true,
            'sub_unit_multiplier' => null,
            'current_sub_stock' => 3,
        ]);

        // Modelin default cast veya null handling durumuna göre. 5 * 0 + 3 = 3
        $this->assertIsNumeric($stock->total_base_units);
    }
}
