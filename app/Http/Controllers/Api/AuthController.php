<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function throttleKey(LoginRequest $request): string
    {
        return Str::lower($request->input('username')).'|'.$request->input('company_code').'|'.$request->ip();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return $this->error("Çok fazla giriş denemesi. {$seconds} saniye sonra tekrar deneyin.", 429);
        }

        $clinicCode = $request->input('clinic_code') ?? $request->input('company_code');

        // 1. Şirketi bul
        if (empty($clinicCode)) {
            RateLimiter::hit($throttleKey, 60);

            return $this->error('Şirket kodu gereklidir.', 422);
        }

        // Şirket koduna bakıyoruz (case-insensitive)
        $company = Company::whereRaw('LOWER(code) = ?', [strtolower($clinicCode)])->first();

        if (! $company) {
            RateLimiter::hit($throttleKey, 60);

            return $this->error('Geçersiz şirket kodu, kullanıcı adı veya şifre', 422);
        }

        // 2. Kullanıcıyı doğrula
        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'company_id' => $company->id,
            'is_active' => true,
        ];

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            /** @var User $user */
            $user = Auth::user();
            $user->load(['company', 'roles', 'clinic']);

            if ($user->hasTwoFactorEnabled()) {
                Log::info('auth.login.2fa_required', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'ip' => $request->ip(),
                ]);
                return $this->success([
                    'requires_2fa' => true,
                    'user' => ['id' => $user->id, 'username' => $user->username],
                ], 'Two-factor authentication required');
            }

            Log::info('auth.login.success', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'ip' => $request->ip(),
            ]);

            return $this->success([
                'user' => $user,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'company' => $user->company,
                'clinic' => $user->clinic,
            ], 'Login successful');
        }

        RateLimiter::hit($throttleKey, 60);
        Log::warning('auth.login.failed', [
            'username' => $request->input('username'),
            'company_code' => $clinicCode,
            'ip' => $request->ip(),
        ]);

        return $this->error('Geçersiz şirket kodu, kullanıcı adı veya şifre', 422);
    }

    public function adminLogin(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = 'admin_login|'.$request->username.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return $this->error("Çok fazla giriş denemesi. {$seconds} saniye sonra tekrar deneyin.", 429);
        }

        $user = User::where('username', $request->username)->first();

        if ($user && $user->hasRole('Super Admin') && Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::clear($throttleKey);
            if (! $user->is_active) {
                return $this->error('Hesabınız pasif durumdadır.', 403);
            }

            Auth::login($user);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $user->load(['company', 'roles']);
            Log::info('auth.admin_login.success', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return $this->success([
                'user' => $user,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'company' => $user->company,
            ], 'Admin login successful');
        }

        RateLimiter::hit($throttleKey, 60);
        Log::warning('auth.admin_login.failed', [
            'username' => $request->input('username'),
            'ip' => $request->ip(),
        ]);

        return $this->error('Geçersiz kullanıcı adı veya şifre', 422);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['company', 'roles']);

        return $this->success([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'company' => $user->company,
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
