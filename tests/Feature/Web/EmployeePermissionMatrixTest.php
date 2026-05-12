<?php

namespace Tests\Feature\Web;

use App\Models\Clinic;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create([
            'name' => 'Merkez Klinik',
        ]);

        foreach (PermissionCatalog::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->manager = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'username' => 'manager-user',
            'is_active' => true,
        ]);

        $role = Role::findOrCreate('Clinic Manager', 'web');
        $role->syncPermissions(PermissionCatalog::all());
        $this->manager->assignRole($role);
    }

    public function test_employee_modal_renders_crud_permission_matrix(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get('/employees?modal=create');

        $response->assertOk();
        $response->assertSee('Personel Yetki Matrisi');
        $response->assertSee('Göster');
        $response->assertSee('Ekle');
        $response->assertSee('Güncelle');
        $response->assertSee('Sil');
        $response->assertSee('Modül Özellikleri');
        $response->assertSee('value="update-clinics"', false);
    }

    public function test_employee_create_and_update_sync_permissions_deterministically(): void
    {
        $this->actingAs($this->manager);

        $createResponse = $this->post('/employees', [
            'name' => 'Yeni Personel',
            'username' => 'yeni-personel',
            'email' => 'yeni@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'clinic_id' => $this->clinic->id,
            // intentionally empty permission payload
        ]);

        $createResponse->assertRedirect(route('employees.index'));

        $employee = User::where('username', 'yeni-personel')->firstOrFail();
        $this->assertCount(0, $employee->permissions);

        $updateResponse = $this->put('/employees/'.$employee->id, [
            'name' => 'Yeni Personel 2',
            'email' => 'yeni2@example.com',
            'clinic_id' => $this->clinic->id,
            'is_active' => 1,
            'permissions' => ['view-stocks', 'update-clinics'],
        ]);

        $updateResponse->assertRedirect(route('employees.index'));
        $employee->refresh();
        $this->assertEqualsCanonicalizing(
            ['view-stocks', 'update-clinics'],
            $employee->permissions->pluck('name')->all()
        );

        $clearResponse = $this->put('/employees/'.$employee->id, [
            'name' => 'Yeni Personel 3',
            'email' => 'yeni3@example.com',
            'clinic_id' => $this->clinic->id,
            'is_active' => 1,
        ]);

        $clearResponse->assertRedirect(route('employees.index'));
        $employee->refresh();
        $this->assertCount(0, $employee->permissions);
    }

    public function test_sidebar_and_routes_follow_permissions(): void
    {
        $restrictedUser = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'username' => 'todo-only-user',
            'is_active' => true,
        ]);
        $restrictedUser->syncPermissions(['view-todos']);

        $this->actingAs($restrictedUser);

        $dashboardResponse = $this->get('/');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee('Ürün Listesi');
        $dashboardResponse->assertDontSee('href="/employees"', false);
        $dashboardResponse->assertSee('Yapılacaklar');

        $this->get('/stocks')->assertForbidden();
        $this->get('/employees')->assertForbidden();
    }

    public function test_clinic_update_policy_accepts_update_clinics_permission(): void
    {
        $user = User::factory()->create([
            'clinic_id' => $this->clinic->id,
            'username' => 'clinic-update-user',
            'is_active' => true,
        ]);
        $user->syncPermissions(['update-clinics']);

        $this->assertTrue($user->can('update', $this->clinic));
    }

    public function test_ajax_employee_list_does_not_render_modal_html_without_requesting_modal(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get('/employees', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['tableHtml', 'modalHtml']);
        $this->assertSame('', (string) data_get($response->json(), 'modalHtml'));
    }
}
