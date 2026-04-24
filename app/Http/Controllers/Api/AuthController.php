<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use JsonResponseTrait;

    /**
     * Throttle key: IP + email kombinasyonu.
     * 🛡️ Güvenlik Notu: Sunucu bir proxy (Nginx, Cloudflare vb.) arkasındaysa 
     * TrustedProxies middleware'i mutlaka doğru ayarlanmalıdır.
     */
    private function throttleKey(LoginRequest $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // 🔒 Brute-force koruması: 5 başarısız denemeden sonra 1 dakika bekleme
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('Too many login attempts', [
                'email' => $request->email,
                'ip'    => $request->ip(),
            ]);

            return $this->error(
                "Çok fazla giriş denemesi. {$seconds} saniye sonra tekrar deneyin.",
                429
            );
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            // ✅ Başarılı girişte throttle sayacını sıfırla
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();
            $user = Auth::user()->load(['company', 'roles']);

            // If 2FA is enabled but not verified yet for this session
            if ($user->hasTwoFactorEnabled()) {
                return $this->success([
                    'requires_2fa' => true,
                    'user' => [
                        'id'    => $user->id,
                        'email' => $user->email
                    ]
                ], 'Two-factor authentication required');
            }

            return $this->success([
                'user'        => $user,
                'roles'       => $user->getRoleNames(),
                // Rol üzerinden gelenler dahil tüm yetkiler
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'company'     => $user->company
            ], 'Login successful');
        }

        // ❌ Başarısız denemede sayacı artır
        RateLimiter::hit($throttleKey, 60); // 60 saniye decay

        Log::warning('Failed login attempt', [
            'email'    => $request->email,
            'ip'       => $request->ip(),
            'attempts' => RateLimiter::attempts($throttleKey),
        ]);

        return $this->error('Geçersiz e-posta veya şifre', 422, [
            'email' => ['Geçersiz e-posta veya şifre girdiniz.']
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['company', 'roles']);

        return $this->success([
            'user'        => $user,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'company'     => $user->company
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Logged out successfully');
    }
}