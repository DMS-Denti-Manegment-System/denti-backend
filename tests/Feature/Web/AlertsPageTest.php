<?php

namespace Tests\Feature\Web;

use App\Models\Clinic;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlertsPageTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create([
            'name' => 'Merkez Klinik',
        ]);

        $this->user = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'username' => 'alerts-user',
            'is_active' => true,
        ]);

        $permissions = [
            'view-stocks',
            'create-stocks',
            'update-stocks',
            'delete-stocks',
            'adjust-stocks',
            'use-stocks',
            'transfer-stocks',
            'approve-transfers',
            'cancel-transfers',
            'view-clinics',
            'create-clinics',
            'update-clinics',
            'delete-clinics',
            'view-reports',
            'export-reports',
            'manage-users',
            'view-audit-logs',
            'view-todos',
            'manage-todos',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('Clinic Manager', 'web');
        $role->syncPermissions($permissions);
        $this->user->assignRole($role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_batch_based_expiry_rows_for_multi_batch_products(): void
    {
        $this->actingAs($this->user);

        $supplier = Supplier::factory()->create();

        $multiProduct = Product::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Kompozit Refil',
            'has_expiration_date' => true,
        ]);

        $batchCritical = Stock::create([
            'product_id' => $multiProduct->id,
            'clinic_id' => $this->clinic->id,
            'supplier_id' => $supplier->id,
            'batch_code' => 'MB-CRT',
            'current_stock' => 5,
            'reserved_stock' => 0,
            'available_stock' => 5,
            'track_expiry' => true,
            'expiry_date' => now()->addDays(4)->toDateString(),
            'expiry_red_days' => 7,
            'expiry_yellow_days' => 20,
            'is_active' => true,
            'currency' => 'TRY',
        ]);

        Stock::create([
            'product_id' => $multiProduct->id,
            'clinic_id' => $this->clinic->id,
            'supplier_id' => $supplier->id,
            'batch_code' => 'MB-NEAR',
            'current_stock' => 5,
            'reserved_stock' => 0,
            'available_stock' => 5,
            'track_expiry' => true,
            'expiry_date' => now()->addDays(12)->toDateString(),
            'expiry_red_days' => 7,
            'expiry_yellow_days' => 20,
            'is_active' => true,
            'currency' => 'TRY',
        ]);

        $singleProduct = Product::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Anestezi Kartuşu',
            'has_expiration_date' => true,
        ]);

        $singleBatch = Stock::create([
            'product_id' => $singleProduct->id,
            'clinic_id' => $this->clinic->id,
            'supplier_id' => $supplier->id,
            'batch_code' => 'SB-001',
            'current_stock' => 8,
            'reserved_stock' => 0,
            'available_stock' => 8,
            'track_expiry' => true,
            'expiry_date' => now()->addDays(6)->toDateString(),
            'expiry_red_days' => 7,
            'expiry_yellow_days' => 20,
            'is_active' => true,
            'currency' => 'TRY',
        ]);

        $response = $this->get('/alerts');

        $response->assertOk();
        $response->assertSee('Parti MB-CRT');
        $response->assertSee('Parti MB-NEAR');
        $response->assertSee('Parti SB-001');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_filters_dynamic_batch_rows_by_type_and_search(): void
    {
        $this->actingAs($this->user);

        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Endo Solüsyon',
            'has_expiration_date' => true,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'clinic_id' => $this->clinic->id,
            'supplier_id' => $supplier->id,
            'batch_code' => 'ENDO-CRIT',
            'current_stock' => 3,
            'reserved_stock' => 0,
            'available_stock' => 3,
            'track_expiry' => true,
            'expiry_date' => now()->addDays(2)->toDateString(),
            'expiry_red_days' => 5,
            'expiry_yellow_days' => 20,
            'is_active' => true,
            'currency' => 'TRY',
        ]);

        Stock::create([
            'product_id' => $product->id,
            'clinic_id' => $this->clinic->id,
            'supplier_id' => $supplier->id,
            'batch_code' => 'ENDO-NEAR',
            'current_stock' => 3,
            'reserved_stock' => 0,
            'available_stock' => 3,
            'track_expiry' => true,
            'expiry_date' => now()->addDays(12)->toDateString(),
            'expiry_red_days' => 5,
            'expiry_yellow_days' => 20,
            'is_active' => true,
            'currency' => 'TRY',
        ]);

        $criticalResponse = $this->get('/alerts?type=critical_expiry');
        $criticalResponse->assertOk();
        $criticalResponse->assertSee('Parti ENDO-CRIT');
        $criticalResponse->assertDontSee('Parti ENDO-NEAR');

        $searchResponse = $this->get('/alerts?search=ENDO-NEAR');
        $searchResponse->assertOk();
        $searchResponse->assertSee('Parti ENDO-NEAR');
        $searchResponse->assertDontSee('Parti ENDO-CRIT');
    }
}
