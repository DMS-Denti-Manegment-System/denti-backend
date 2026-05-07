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
    use HasApiTokens, HasFactory, HasRoles, Notifiable, Tenantable;

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
        static $requestCache = [];
        if (isset($requestCache[$this->id])) {
            return $requestCache[$this->id];
        }

        return $requestCache[$this->id] = \Illuminate\Support\Facades\Cache::remember("user_is_super_admin_{$this->id}", 3600, function () {
            // 🛡️ TenantScope döngüsünü kırmak için rollerini scope olmadan kontrol et
            return $this->roles()->withoutGlobalScopes()->where('name', self::ROLE_SUPER_ADMIN)->exists();
        });
    }
}
