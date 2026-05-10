<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

/**
 * Spatie RoleMiddleware ile aynı mantık; kullanıcı $request->user() üzerinden alınır (Sanctum uyumu).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        $user = $request->user() ?? auth()->user();

        if (! $user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (! method_exists($user, 'hasAnyRole')) {
            throw UnauthorizedException::missingTraitHasRoles($user);
        }

        setPermissionsTeamId($user->company_id ?? 0);

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        $roles = explode('|', $role);

        if (! $user->hasAnyRole($roles)) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }
}
