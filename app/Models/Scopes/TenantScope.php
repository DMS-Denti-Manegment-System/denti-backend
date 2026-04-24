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
        // User modeli için global scope'u devre dışı bırakıyoruz 
        // çünkü Sanctum user'ı çekerken bu scope sonsuz döngüye giriyor.
        if ($model instanceof \App\Models\User) {
            return;
        }

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
