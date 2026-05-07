<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Rolleri ve Yetkileri oluştur
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Super Admin Kullanıcısı oluştur (Şirket bağımsız)
        $superAdmin = User::create([
            'company_id' => null,
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@denti.com',
            'password' => Hash::make('superadmin123'),
            'is_active' => true,
        ]);

        $superAdmin->assignRole('Super Admin');
    }
}
