<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\StockRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StockRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StockRequestService::class);
    }

    public function test_create_request_throws_authorization_exception_for_idor()
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();

        // User belongs to Clinic A
        $user = User::factory()->create(['clinic_id' => $clinicA->id]);
        Auth::login($user);

        $product = Product::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'clinic_id' => $clinicB->id,
            'current_stock' => 100,
        ]);

        $this->expectException(AuthorizationException::class);

        // User from Clinic A tries to create a request on behalf of Clinic B (IDOR attempt)
        $this->service->createRequest([
            'requester_clinic_id' => $clinicB->id,
            'requested_from_clinic_id' => $clinicA->id,
            'stock_id' => $stock->id,
            'requested_quantity' => 10,
        ]);
    }

    public function test_create_request_succeeds_when_clinic_matches()
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();

        // User belongs to Clinic A
        $user = User::factory()->create(['clinic_id' => $clinicA->id]);
        Auth::login($user);

        $product = Product::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'clinic_id' => $clinicB->id,
            'current_stock' => 100,
            'available_stock' => 100,
        ]);

        $request = $this->service->createRequest([
            'requester_clinic_id' => $clinicA->id,
            'requested_from_clinic_id' => $clinicB->id,
            'stock_id' => $stock->id,
            'requested_quantity' => 10,
            'requested_by' => 'Test User',
        ]);

        $this->assertNotNull($request);
        $this->assertEquals('pending', $request->status);
    }

    public function test_reject_request_updates_status_correctly()
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();

        $product = Product::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'clinic_id' => $clinicB->id,
            'current_stock' => 100,
        ]);

        // Since there is no factory, we use the service to create it (need to temporarily impersonate User A)
        $userA = User::factory()->create(['clinic_id' => $clinicA->id]);
        Auth::login($userA);

        $stockRequest = $this->service->createRequest([
            'requester_clinic_id' => $clinicA->id,
            'requested_from_clinic_id' => $clinicB->id,
            'stock_id' => $stock->id,
            'requested_quantity' => 10,
            'requested_by' => 'User A',
        ]);

        // User belongs to Clinic B (The one rejecting)
        $user = User::factory()->create(['clinic_id' => $clinicB->id]);
        Auth::login($user);

        $result = $this->service->rejectRequest($stockRequest->id, 'No stock available', 'Admin');

        $this->assertTrue($result);
        $this->assertDatabaseHas('stock_requests', [
            'id' => $stockRequest->id,
            'status' => 'rejected',
            'rejection_reason' => 'No stock available',
        ]);
    }
}
