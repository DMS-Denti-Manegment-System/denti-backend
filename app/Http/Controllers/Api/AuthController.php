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
use App\Models\Company;

class AuthController extends Controller
{
    use JsonResponseTrait;

    private function throttleKey(LoginRequest $request): string
    {
        return Str::lower($request->input('username')) . '|' . $request->input('clinic_code') . '|' . $request->ip();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error("Çok fazla giriş denemesi. {$seconds} saniye sonra tekrar deneyin.", 429);
        }

        // 1. Şirketi veya Kliniği bul
        if (!$request->clinic_code) {
            RateLimiter::hit($throttleKey, 60);
            return $this->error('Klinik kodu gereklidir.', 422);
        }

        // Şirket koduna bakıyoruz
        $company = Company::where('code', $request->clinic_code)->first();
        
        if (!$company) {
            RateLimiter::hit($throttleKey, 60);
            return $this->error('Geçersiz klinik kodu, kullanıcı adı veya şifre', 422);
        }

        // 2. Kullanıcıyı doğrula
        $credentials = [
            'username'   => $request->username, 
            'password'   => $request->password, 
            'company_id' => $company->id,
            'is_active'  => true
        ];

        // Eğer klinik kodu ile girildiyse, kullanıcının o kliniğe ait olduğundan emin ol (Super Admin değilse)
        // Not: Bazı kullanıcılar sadece şirkete bağlı olabilir (clinic_id null).
        // Eğer klinikId doluysa ve kullanıcı Super Admin değilse, clinic_id kontrolü eklenebilir.
        // Ancak şimdilik sadece company_id üzerinden gidelim, login sonrası clinic_id session'da tutulabilir.

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            
            $user = Auth::user()->load(['company', 'roles', 'clinic']);

            if ($user->hasTwoFactorEnabled()) {
                return $this->success([
                    'requires_2fa' => true,
                    'user' => ['id' => $user->id, 'username' => $user->username]
                ], 'Two-factor authentication required');
            }

            return $this->success([
                'user'        => $user,
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'company'     => $user->company,
                'clinic'      => $user->clinic
            ], 'Login successful');
        }

        RateLimiter::hit($throttleKey, 60);

        return $this->error('Geçersiz klinik kodu, kullanıcı adı veya şifre', 422);
    }

    public function adminLogin(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if ($user && $user->hasRole('Super Admin') && \Hash::check($request->password, $user->password)) {
            if (!$user->is_active) {
                return $this->error('Hesabınız pasif durumdadır.', 403);
            }

            Auth::login($user);
            $request->session()->regenerate();
            
            $user->load(['company', 'roles']);

            return $this->success([
                'user'        => $user,
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'company'     => $user->company
            ], 'Admin login successful');
        }

        return $this->error('Geçersiz kullanıcı adı veya şifre', 422);
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