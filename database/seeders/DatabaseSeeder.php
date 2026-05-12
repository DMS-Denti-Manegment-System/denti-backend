<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\RoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Sistem izinlerini oluştur
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Uygulama rollerini oluştur
        app(RoleService::class)->ensureRoles();

        $owner = User::query()
            ->where('username', config('denti.owner.username', 'admin'))
            ->first();

        if (! $owner) {
            $owner = User::create([
                'name' => config('denti.owner.name', 'Klinik Yetkilisi'),
                'username' => config('denti.owner.username', 'admin'),
                'email' => config('denti.owner.email', 'admin@denti.local'),
                'password' => Hash::make(config('denti.owner.password', 'admin12345')),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $owner->assignRole('Admin');
    }
}
