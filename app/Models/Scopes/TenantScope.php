<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Super Admin her şeyi görebilir
            if ($user->hasRole('Super Admin')) {
                return;
            }

            if ($user->company_id) {
                $builder->where($model->getTable() . '.company_id', $user->company_id);
            }
        }
    }
}
