<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Kullanıcı login olmuş ve 2FA aktifse ama session'da doğrulanmamışsa
        if ($user && $user->hasTwoFactorEnabled() && ! $request->session()->has('2fa_verified')) {

            // İstisna: 2FA doğrulama endpoint'lerine erişebilmeli
            if ($request->is('api/auth/2fa/verify') || $request->is('api/auth/logout')) {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication required.',
                'requires_2fa' => true,
            ], 403);
        }

        return $next($request);
    }
}
