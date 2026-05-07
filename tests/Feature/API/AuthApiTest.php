<?php

namespace Tests\Feature\API;

use App\Models\Clinic;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Clinic $clinic;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'code' => 'DEMO',
        ]);

        $this->clinic = Clinic::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'clinic_id' => $this->clinic->id,
            'username' => 'admin',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'company_code' => 'DEMO',
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'roles',
                    'permissions',
                    'company',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_login_with_invalid_company_code()
    {
        $response = $this->postJson('/api/login', [
            'company_code' => 'INVALID',
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Geçersiz şirket kodu, kullanıcı adı veya şifre',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_login_with_invalid_password()
    {
        $response = $this->postJson('/api/login', [
            'company_code' => 'DEMO',
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Geçersiz şirket kodu, kullanıcı adı veya şifre',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inactive_user_cannot_login()
    {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/login', [
            'company_code' => 'DEMO',
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_get_profile()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'roles',
                    'permissions',
                    'company',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_logout()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function login_is_rate_limited()
    {
        // 5 başarısız deneme
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'company_code' => 'DEMO',
                'username' => 'admin',
                'password' => 'wrongpassword',
            ]);
        }

        // 6. deneme rate limited
        $response = $this->postJson('/api/login', [
            'company_code' => 'DEMO',
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonPath('message', fn ($message) => str_starts_with($message, 'Çok fazla giriş denemesi'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login_using_clinic_code_instead_of_company_code()
    {
        $response = $this->postJson('/api/login', [
            'clinic_code' => 'DEMO',
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'company',
                ],
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function super_admin_can_login_without_clinic_or_company_code()
    {
        $superAdmin = User::factory()->create([
            'username' => 'super',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        \Spatie\Permission\Models\Role::create(['name' => 'Super Admin']);
        $superAdmin->assignRole('Super Admin');

        $response = $this->postJson('/api/admin/login', [
            'username' => 'super',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function normal_user_cannot_login_without_clinic_or_company_code()
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Şirket kodu gereklidir.',
            ]);
    }
}
