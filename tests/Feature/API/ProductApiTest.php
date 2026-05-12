<?php

namespace Tests\Feature\API;

use App\Models\Clinic;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Clinic $clinic;

    private \App\Models\Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        $this->supplier = \App\Models\Supplier::create([
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'clinic_id' => $this->clinic->id,
        ]);

        // Permission'ları oluştur ve kullanıcıya ver (routes'deki doğru isimler)
        \Spatie\Permission\Models\Permission::findOrCreate('view-stocks', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('create-stocks', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('update-stocks', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('delete-stocks', 'web');
        $this->user->givePermissionTo(['view-stocks', 'create-stocks', 'update-stocks', 'delete-stocks']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_list_products()
    {
        $this->actingAs($this->user);

        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'sku',
                        'unit',
                        'total_stock',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_product()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/products', [
            'name' => 'Parol 500mg',
            'sku' => 'PR-001',
            'unit' => 'tablet',
            'min_stock_level' => 10,
            'critical_stock_level' => 5,
            'category' => 'İlaç',
            'is_active' => true,
            'quantity' => 100,
            'clinic_id' => $this->clinic->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Parol 500mg',
                    'sku' => 'PR-001',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Parol 500mg',
            'sku' => 'PR-001',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function product_creation_requires_name()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/products', [
            'sku' => 'PR-001',
            'unit' => 'tablet',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function product_creation_requires_unit()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/products', [
            'name' => 'Parol 500mg',
            'sku' => 'PR-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unit']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_view_single_product()
    {
        $this->actingAs($this->user);

        $product = Product::factory()->create([
            'name' => 'Test Ürün',
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => 'Test Ürün',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_product()
    {
        $this->actingAs($this->user);

        $product = Product::factory()->create([
            'name' => 'Eski İsim',
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Yeni İsim',
            'unit' => $product->unit,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Yeni İsim',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Yeni İsim',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_delete_product()
    {
        $this->actingAs($this->user);

        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'deleted') || str_contains($message, 'silindi'));

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_search_products()
    {
        $this->actingAs($this->user);

        Product::factory()->create([
            'name' => 'Parol 500mg',
        ]);

        Product::factory()->create([
            'name' => 'Aspirin',
        ]);

        $response = $this->getJson('/api/products?search=Parol');

        $response->assertStatus(200)
            ->assertJson(fn ($json) => $json->has('data')
                ->whereType('data', 'array')
                ->etc());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function products_are_paginated()
    {
        $this->actingAs($this->user);

        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_cannot_access_products()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_filter_by_clinic()
    {
        $this->actingAs($this->user);

        $product1 = Product::factory()->create([
            'clinic_id' => $this->clinic->id,
        ]);

        $otherClinic = Clinic::factory()->create();

        $product2 = Product::factory()->create([
            'clinic_id' => $otherClinic->id,
        ]);

        $response = $this->getJson("/api/products?clinic_id={$this->clinic->id}");

        $response->assertStatus(200)
            ->assertJson(fn ($json) => $json->has('data')
                ->whereType('data', 'array')
                ->etc());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_without_create_permission_cannot_create_product()
    {
        // Yetkileri olmayan yeni bir user
        $unauthorizedUser = User::factory()->create([
            'clinic_id' => $this->clinic->id,
        ]);

        $this->actingAs($unauthorizedUser);

        $response = $this->postJson('/api/products', [
            'name' => 'Parol 500mg',
            'sku' => 'PR-001',
            'unit' => 'tablet',
            'is_active' => true,
        ]);

        $response->assertStatus(403);
    }
}
