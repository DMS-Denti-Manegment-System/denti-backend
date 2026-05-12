<?php

namespace Tests\Feature\API;

use App\Models\Clinic;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserPermissionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_user_store_and_update_sync_permissions_deterministically(): void
    {
        $clinic = Clinic::factory()->create();

        foreach (PermissionCatalog::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $manager = User::factory()->create([
            'clinic_id' => $clinic->id,
            'username' => 'api-manager',
            'is_active' => true,
        ]);
        $manager->syncPermissions(PermissionCatalog::all());

        Sanctum::actingAs($manager);

        $createResponse = $this->postJson('/api/users', [
            'name' => 'Api Employee',
            'username' => 'api-employee',
            'email' => 'api-employee@example.com',
            'password' => 'Password123!',
            'clinic_id' => $clinic->id,
        ]);

        $createResponse->assertStatus(201);
        $createdUserId = (int) data_get($createResponse->json(), 'data.id');
        $createdUser = User::findOrFail($createdUserId);
        $this->assertCount(0, $createdUser->permissions);

        $updateResponse = $this->putJson('/api/users/'.$createdUserId, [
            'name' => 'Api Employee Updated',
            'clinic_id' => $clinic->id,
            'permissions' => ['view-stocks', 'update-clinics'],
        ]);
        $updateResponse->assertOk();

        $createdUser->refresh();
        $this->assertEqualsCanonicalizing(
            ['view-stocks', 'update-clinics'],
            $createdUser->permissions->pluck('name')->all()
        );

        $clearResponse = $this->putJson('/api/users/'.$createdUserId, [
            'name' => 'Api Employee Cleared',
            'clinic_id' => $clinic->id,
        ]);
        $clearResponse->assertOk();

        $createdUser->refresh();
        $this->assertCount(0, $createdUser->permissions);
    }
}
