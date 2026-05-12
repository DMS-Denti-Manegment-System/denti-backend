<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $domain
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $alert_emails
 * @property string $subscription_plan
 * @property int $max_users
 * @property string $status
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Clinic[] $clinics
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'domain',
        'address',
        'phone',
        'email',
        'alert_emails',
        'subscription_plan',
        'max_users',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_users' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }
}
