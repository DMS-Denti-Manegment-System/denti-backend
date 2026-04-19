<?php

namespace App\Models;

use App\Traits\Tenantable;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use Tenantable;

    protected $fillable = [
        'name',
        'guard_name',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'company_id' => 'integer',
    ];
}
