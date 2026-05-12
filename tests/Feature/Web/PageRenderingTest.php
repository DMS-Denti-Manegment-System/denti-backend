<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Clinic;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $user;

    private Product $product;

    private Category $category;

    private Supplier $supplier;

    private Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();

        $this->user = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'username' => 'blade-user',
            'is_active' => true,
        ]);

        $permissionNames = [
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
            'manage-company',
            'view-audit-logs',
            'view-todos',
            'manage-todos',
            'view-categories',
            'create-categories',
            'update-categories',
            'delete-categories',
            'view-suppliers',
            'create-suppliers',
            'update-suppliers',
            'delete-suppliers',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $role = Role::findOrCreate('Clinic Manager', 'web');
        $role->syncPermissions($permissionNames);
        $this->user->assignRole($role);

        $this->category = Category::create([
            'name' => 'Sterilizasyon',
            'color' => '#0d6efd',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Demo Supplier',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'clinic_id' => $this->clinic->id,
            'name' => 'Demo Product',
            'sku' => 'DP-001',
            'unit' => 'adet',
            'category' => 'Sterilizasyon',
            'brand' => 'Demo',
            'min_stock_level' => 10,
            'critical_stock_level' => 5,
            'yellow_alert_level' => 10,
            'red_alert_level' => 5,
            'is_active' => true,
        ]);

        $this->todo = Todo::create([
            'category_id' => $this->category->id,
            'title' => 'Demo todo',
            'description' => 'Blade render smoke test',
            'completed' => false,
        ]);
    }

    public function test_main_web_pages_render_for_company_user(): void
    {
        $this->actingAs($this->user);

        $urls = [
            '/',
            '/stocks',
            '/stocks?modal=create',
            "/stocks?modal=edit&edit={$this->product->id}",
            '/stock-categories',
            "/stock-categories?modal=edit&edit={$this->category->id}",
            '/suppliers',
            "/suppliers?modal=edit&edit={$this->supplier->id}",
            '/clinics',
            "/clinics?modal=edit&edit={$this->clinic->id}",
            '/stock-requests',
            '/stock-requests?modal=create',
            '/alerts',
            '/todos',
            "/todos?modal=edit&edit={$this->todo->id}",
            '/reports',
            '/employees',
            '/employees?modal=create',
            '/roles',
            '/roles?modal=create',
            '/profile',
            "/stock/products/{$this->product->id}",
        ];

        foreach ($urls as $url) {
            $response = $this->get($url);
            if ($response->status() !== 200) {
                dump('Failed URL: '.$url.' Status: '.$response->status());
            }
            $response->assertOk();
        }
    }

    public function test_admin_companies_page_is_not_exposed(): void
    {
        $this->get('/admin/companies')->assertNotFound();
        $this->get('/admin/companies?modal=create')->assertNotFound();
    }
}
