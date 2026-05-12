<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-stocks');
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('view-stocks');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-stocks');
    }

    public function update(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('update-stocks');
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('delete-stocks');
    }

    public function adjust(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('adjust-stocks');
    }

    public function use(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('use-stocks');
    }
}
