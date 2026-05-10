<?php

namespace App\Support;

use App\Models\User;

class PermissionCatalog
{
    /**
     * Canonical permission labels used by web/API surfaces.
     */
    private const LABELS = [
        'view-stocks' => 'Stokları Gör',
        'create-stocks' => 'Stok/Ürün Ekle',
        'update-stocks' => 'Stok/Ürün Güncelle',
        'delete-stocks' => 'Stok/Ürün Sil',
        'adjust-stocks' => 'Stok Düzeltme',
        'use-stocks' => 'Stok Kullanımı',
        'transfer-stocks' => 'Transfer Talebi Oluştur',
        'approve-transfers' => 'Transfer Onayla/Sevk',
        'cancel-transfers' => 'Transfer İptal/Reddet',
        'view-clinics' => 'Klinikleri Gör',
        'create-clinics' => 'Klinik Ekle',
        'update-clinics' => 'Klinik Güncelle',
        'delete-clinics' => 'Klinik Sil',
        'view-reports' => 'Raporları Gör',
        'export-reports' => 'Rapor Dışa Aktar',
        'manage-users' => 'Personel Yönetimi',
        'manage-company' => 'Şirket Ayarları',
        'view-audit-logs' => 'İşlem Kayıtlarını Gör',
        'view-todos' => 'Görevleri Gör',
        'manage-todos' => 'Görev Yönetimi',
    ];

    /**
     * CRUD matrix rows. Null values represent unavailable CRUD action.
     */
    private const CRUD_MATRIX = [
        [
            'module' => 'Stoklar',
            'permissions' => [
                'show' => 'view-stocks',
                'create' => 'create-stocks',
                'update' => 'update-stocks',
                'delete' => 'delete-stocks',
            ],
        ],
        [
            'module' => 'Klinikler',
            'permissions' => [
                'show' => 'view-clinics',
                'create' => 'create-clinics',
                'update' => 'update-clinics',
                'delete' => 'delete-clinics',
            ],
        ],
        [
            'module' => 'Raporlar',
            'permissions' => [
                'show' => 'view-reports',
                'create' => null,
                'update' => null,
                'delete' => null,
            ],
        ],
        [
            'module' => 'Görevler',
            'permissions' => [
                'show' => 'view-todos',
                'create' => null,
                'update' => null,
                'delete' => null,
            ],
        ],
        [
            'module' => 'Personel',
            'permissions' => [
                'show' => 'manage-users',
                'create' => null,
                'update' => null,
                'delete' => null,
            ],
        ],
    ];

    /**
     * Non-CRUD feature permissions rendered under matrix.
     */
    private const FEATURE_PERMISSIONS = [
        'Stok Özellikleri' => [
            'adjust-stocks',
            'use-stocks',
            'transfer-stocks',
            'approve-transfers',
            'cancel-transfers',
        ],
        'Rapor & Sistem Özellikleri' => [
            'export-reports',
            'view-audit-logs',
            'manage-company',
            'manage-todos',
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(string $permission): string
    {
        return self::LABELS[$permission] ?? $permission;
    }

    /**
     * @return array<int, array{module:string, permissions:array{show:?string,create:?string,update:?string,delete:?string}}>
     */
    public static function crudMatrix(): array
    {
        return self::CRUD_MATRIX;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function featurePermissions(): array
    {
        return self::FEATURE_PERMISSIONS;
    }

    /**
     * Normalize and filter requested permissions to known and assignable ones.
     *
     * @param  array<int, string>  $requested
     * @return array<int, string>
     */
    public static function sanitizeForAssigner(array $requested, User $assigner): array
    {
        $known = array_flip(self::all());
        $normalized = [];

        foreach ($requested as $permission) {
            if (! is_string($permission)) {
                continue;
            }

            $permission = trim($permission);
            if ($permission === '' || ! isset($known[$permission])) {
                continue;
            }

            $normalized[$permission] = true;
        }

        $requestedPermissions = array_keys($normalized);
        if ($assigner->isSuperAdmin()) {
            return $requestedPermissions;
        }

        $assignerPermissions = array_flip($assigner->getAllPermissions()->pluck('name')->all());

        return array_values(array_filter(
            $requestedPermissions,
            static fn (string $permission) => isset($assignerPermissions[$permission])
        ));
    }
}

