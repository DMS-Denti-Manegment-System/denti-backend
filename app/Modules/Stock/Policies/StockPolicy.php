<?php

namespace App\Modules\Stock\Policies;

use App\Models\User;
use App\Modules\Stock\Models\Stock;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-stocks');
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->company_id === $stock->company_id && $user->hasPermissionTo('view-stocks');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-stocks');
    }

    public function update(User $user, Stock $stock): bool
    {
        return $user->company_id === $stock->company_id && $user->hasPermissionTo('update-stocks');
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $user->company_id === $stock->company_id && $user->hasPermissionTo('delete-stocks');
    }

    public function adjust(User $user, Stock $stock): bool
    {
        return $user->company_id === $stock->company_id && $user->hasPermissionTo('adjust-stocks');
    }

    public function use(User $user, Stock $stock): bool
    {
        return $user->company_id === $stock->company_id && $user->hasPermissionTo('use-stocks');
    }
}
