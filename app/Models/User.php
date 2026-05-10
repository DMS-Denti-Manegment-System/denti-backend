<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles {
        assignRole as protected spatieAssignRole;
        givePermissionTo as protected spatieGivePermissionTo;
        syncPermissions as protected spatieSyncPermissions;
        syncRoles as protected spatieSyncRoles;
    }

    use Notifiable, Tenantable;

    const ROLE_SUPER_ADMIN = 'Super Admin';

    /** Veritabanı / seeder ile aynı isim (eski 'Owner' sabiti hatalıydı) */
    const ROLE_OWNER = 'Company Owner';

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'company_id',
        'clinic_id',
        'is_active',
    ];

    /**
     * Check if 2FA is enabled and confirmed.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    public function assignRole(...$roles)
    {
        $this->setPermissionTeamContext();

        return $this->spatieAssignRole(...$this->rolesForCurrentTeam($roles));
    }

    public function syncRoles(...$roles)
    {
        $this->setPermissionTeamContext();

        return $this->spatieSyncRoles(...$roles);
    }

    public function givePermissionTo(...$permissions)
    {
        $this->setPermissionTeamContext();

        return $this->spatieGivePermissionTo(...$permissions);
    }

    public function syncPermissions(...$permissions)
    {
        $this->setPermissionTeamContext();

        return $this->spatieSyncPermissions(...$permissions);
    }

    private function setPermissionTeamContext(): void
    {
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($this->company_id ?? 0);
        }
    }

    private function rolesForCurrentTeam(array $roles): array
    {
        $teamId = $this->company_id ?? 0;

        return collect($this->flattenRoleArguments($roles))
            ->map(function ($role) use ($teamId) {
                if ($role instanceof \Spatie\Permission\Contracts\Role) {
                    if ($role->company_id !== null && (int) $role->company_id === (int) $teamId) {
                        return $role;
                    }

                    return $this->copyRoleToTeam($role, $teamId);
                }

                if (is_string($role)) {
                    $existingRole = Role::withoutGlobalScopes()
                        ->where('name', $role)
                        ->where('guard_name', $this->getDefaultGuardName())
                        ->where('company_id', $teamId)
                        ->first();

                    if ($existingRole) {
                        return $existingRole;
                    }
                }

                return $role;
            })
            ->all();
    }

    private function flattenRoleArguments(array $roles): array
    {
        $flattened = [];

        foreach ($roles as $role) {
            if (is_array($role)) {
                array_push($flattened, ...$this->flattenRoleArguments($role));
            } else {
                $flattened[] = $role;
            }
        }

        return $flattened;
    }

    private function copyRoleToTeam(\Spatie\Permission\Contracts\Role $sourceRole, int $teamId): Role
    {
        $role = Role::withoutGlobalScopes()->firstOrCreate([
            'name' => $sourceRole->name,
            'guard_name' => $sourceRole->guard_name,
            'company_id' => $teamId,
        ]);

        $permissionNames = $sourceRole->permissions()->pluck('name')->all();
        if ($permissionNames !== []) {
            $role->syncPermissions($permissionNames);
        }

        return $role;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * Get the company that owns the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the clinic that the user belongs to.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Clinic::class);
    }

    /**
     * Super Admin kontrolü (RAM/Cache üzerinden).
     * TenantScope'da sonsuz döngüyü kırmak için kullanılır.
     */
    public function isSuperAdmin(): bool
    {
        return \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', self::class)
            ->where('model_has_roles.model_id', $this->id)
            ->where('name', self::ROLE_SUPER_ADMIN)
            ->exists();
    }
}
