<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockDashboardConsistencyTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function stock_stats_total_items_excludes_soft_deleted_products(): void
    {
        $company = Company::factory()->create();
        $clinic = Clinic::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'clinic_id' => $clinic->id,
        ]);

        Stock::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'clinic_id' => $clinic->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'available_stock' => 10,
            'is_active' => true,
            'track_expiry' => false,
            'currency' => 'TRY',
        ]);

        $stockService = app(StockService::class);
        $initial = $stockService->getStockStats($company->id);
        $this->assertSame(1, $initial['total_items']);

        $product->delete();

        $afterDelete = $stockService->getStockStats($company->id);
        $this->assertSame(0, $afterDelete['total_items']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_stats_cache_is_invalidated_after_product_changes(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $clinic = Clinic::factory()->create(['company_id' => $company->id]);

        Product::factory()->create([
            'company_id' => $company->id,
            'clinic_id' => $clinic->id,
        ]);

        $service = app(DashboardStatsService::class);

        $first = $service->getStatsForUser($user);
        $this->assertSame(1, $first['total_stock_items']);

        Product::factory()->create([
            'company_id' => $company->id,
            'clinic_id' => $clinic->id,
        ]);

        $second = $service->getStatsForUser($user);
        $this->assertSame(2, $second['total_stock_items']);
    }
}
