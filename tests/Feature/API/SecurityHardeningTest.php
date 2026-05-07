<?php

namespace Tests\Feature\API;

use App\Models\Clinic;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_request_id_header(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
        $response->assertHeader('X-Request-Id');
    }

    public function test_health_endpoint_returns_checks(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['checks' => ['db', 'cache']],
                'errors',
                'meta',
            ]);
    }

    public function test_tenant_isolation_blocks_other_company_product_read(): void
    {
        $companyA = Company::factory()->create();
        $clinicA = Clinic::factory()->create(['company_id' => $companyA->id]);
        $userA = User::factory()->create(['company_id' => $companyA->id, 'clinic_id' => $clinicA->id]);

        $companyB = Company::factory()->create();
        $productB = Product::factory()->create(['company_id' => $companyB->id]);

        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view-stocks']);
        $userA->givePermissionTo('view-stocks');

        $this->actingAs($userA);

        $this->getJson('/api/products/'.$productB->id)
            ->assertStatus(404);
    }
}
