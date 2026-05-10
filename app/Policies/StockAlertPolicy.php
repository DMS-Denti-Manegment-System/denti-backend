<?php

namespace App\Policies;

use App\Models\StockAlert;
use App\Models\User;

class StockAlertPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-stocks');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StockAlert $stockAlert): bool
    {
        // Tenant check - user can only view alerts from their company
        if ($stockAlert->company_id !== $user->company_id) {
            return false;
        }

        return $user->hasPermissionTo('view-stocks');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Alerts are typically created by system, not users
        return $user->isSuperAdmin() || $user->hasRole(User::ROLE_OWNER);
    }

    /**
     * Determine whether the user can resolve the alert.
     */
    public function resolve(User $user, StockAlert $stockAlert): bool
    {
        // Tenant check
        if ($stockAlert->company_id !== $user->company_id) {
            return false;
        }

        return $user->hasPermissionTo('adjust-stocks');
    }

    /**
     * Determine whether the user can dismiss the alert.
     */
    public function dismiss(User $user, StockAlert $stockAlert): bool
    {
        // Tenant check
        if ($stockAlert->company_id !== $user->company_id) {
            return false;
        }

        return $user->hasPermissionTo('adjust-stocks');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockAlert $stockAlert): bool
    {
        // Tenant check
        if ($stockAlert->company_id !== $user->company_id) {
            return false;
        }

        return $user->hasPermissionTo('delete-stocks');
    }

    /**
     * Determine whether the user can bulk resolve alerts.
     */
    public function bulkResolve(User $user): bool
    {
        return $user->hasPermissionTo('adjust-stocks');
    }

    /**
     * Determine whether the user can bulk dismiss alerts.
     */
    public function bulkDismiss(User $user): bool
    {
        return $user->hasPermissionTo('adjust-stocks');
    }

    /**
     * Determine whether the user can bulk delete alerts.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete-stocks');
    }
}
