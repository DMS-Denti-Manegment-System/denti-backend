<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Yetki önbelleğini temizle
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // TEMEL YETKİLER
        $permissions = [
            // Stok yetkileri
            'view-stocks',
            'create-stocks',
            'update-stocks',
            'delete-stocks',
            'adjust-stocks',
            'use-stocks',
            
            // Klinik yetkileri
            'view-clinics',
            'create-clinics',
            'update-clinics',
            'delete-clinics',

            // Rapor yetkileri
            'view-reports',
            'export-reports',

            // Şirket ve Kullanıcı yönetimi
            'manage-users',
            'manage-company',
            'view-audit-logs',

            // Todo yetkileri
            'view-todos',
            'manage-todos',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // ROLLERİ OLUŞTUR VE YETKİLERİ ATA

        // 1. Super Admin (Tüm yetkiler)
        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        // Super admin her şeye erişebilir (AuthServiceProvider'da gate check ile de yapılabilir)

        // 2. Company Owner (Şirket sahibi)
        $owner = Role::findOrCreate('Company Owner', 'web');
        $owner->givePermissionTo(Permission::all());

        // 3. Clinic Manager
        $manager = Role::findOrCreate('Clinic Manager', 'web');
        $manager->givePermissionTo([
            'view-stocks', 'create-stocks', 'update-stocks', 'adjust-stocks', 'use-stocks',
            'view-clinics', 'view-reports', 'export-reports', 'manage-users', 'view-todos', 'manage-todos'
        ]);

        // 4. Doctor
        $doctor = Role::findOrCreate('Doctor', 'web');
        $doctor->givePermissionTo([
            'view-stocks', 'use-stocks', 'view-clinics', 'view-todos', 'manage-todos'
        ]);

        // 5. Secretary
        $secretary = Role::findOrCreate('Secretary', 'web');
        $secretary->givePermissionTo([
            'view-stocks', 'use-stocks', 'view-clinics', 'view-todos', 'manage-todos'
        ]);
    }
}
