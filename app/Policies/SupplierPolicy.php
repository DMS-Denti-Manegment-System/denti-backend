<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('view-suppliers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-suppliers') || $user->hasRole(User::ROLE_OWNER);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('update-suppliers') || $user->hasRole(User::ROLE_OWNER);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('delete-suppliers') || $user->hasRole(User::ROLE_OWNER);
    }
}
