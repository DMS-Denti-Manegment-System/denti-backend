<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function throttleKey(LoginRequest $request): string
    {
        return Str::lower($request->input('username')).'|single-company|'.$request->ip();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return $this->error("Çok fazla giriş denemesi. {$seconds} saniye sonra tekrar deneyin.", 429);
        }

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'is_active' => true,
        ];

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            /** @var User $user */
            $user = Auth::user();
            $user->load(['roles', 'clinic']);

            if ($user->hasTwoFactorEnabled()) {
                Log::info('auth.login.2fa_required', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]);

                return $this->success([
                    'requires_2fa' => true,
                    'user' => ['id' => $user->id, 'username' => $user->username],
                ], 'Two-factor authentication required');
            }

            Log::info('auth.login.success', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return $this->success([
                'user' => $user,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'clinic' => $user->clinic,
            ], 'Login successful');
        }

        RateLimiter::hit($throttleKey, 60);
        Log::warning('auth.login.failed', [
            'username' => $request->input('username'),
            'ip' => $request->ip(),
        ]);

        return $this->error('Geçersiz kullanıcı adı veya şifre', 422);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['roles']);

        return $this->success([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(null, 'Logged out successfully');
    }
}
