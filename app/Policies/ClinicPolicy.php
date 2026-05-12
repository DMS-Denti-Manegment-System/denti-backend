<?php

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClinicPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Clinic $clinic): bool
    {
        return $user->hasPermissionTo('view-clinics');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-clinics') || $user->hasRole(User::ROLE_OWNER);
    }

    public function update(User $user, Clinic $clinic): bool
    {
        return $user->hasPermissionTo('update-clinics') || $user->hasRole(User::ROLE_OWNER);
    }

    public function delete(User $user, Clinic $clinic): bool
    {
        return $user->hasPermissionTo('delete-clinics') || $user->hasRole(User::ROLE_OWNER);
    }
}
