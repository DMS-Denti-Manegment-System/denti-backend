<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

/**
 * Spatie PermissionMiddleware yerine: kullanıcıyı önce $request->user() ile alır.
 * Sanctum (özellikle API token) ile auth:sanctum sonrası bazen varsayılan guard boş kalabiliyor.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user() ?? auth()->user();

        if (! $user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (! method_exists($user, 'canAny')) {
            throw UnauthorizedException::missingTraitHasRoles($user);
        }

        $permissions = explode('|', $permission);

        if (! $user->canAny($permissions)) {
            throw UnauthorizedException::forPermissions($permissions);
        }

        return $next($request);
    }
}
