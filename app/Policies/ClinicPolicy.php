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
        return $user->isSuperAdmin() || $user->company_id === $clinic->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-clinics') || $user->hasRole('Company Owner');
    }

    public function update(User $user, Clinic $clinic): bool
    {
        return ($user->isSuperAdmin() || $user->company_id === $clinic->company_id) 
            && ($user->hasPermissionTo('edit-clinics') || $user->hasRole('Company Owner'));
    }

    public function delete(User $user, Clinic $clinic): bool
    {
        return ($user->isSuperAdmin() || $user->company_id === $clinic->company_id) 
            && ($user->hasPermissionTo('delete-clinics') || $user->hasRole('Company Owner'));
    }
}
