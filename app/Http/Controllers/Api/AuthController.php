<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use JsonResponseTrait;

    public function login(LoginRequest $request): JsonResponse
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();
            $user = Auth::user()->load(['company', 'roles']);

            // If 2FA is enabled but not verified yet for this session
            if ($user->hasTwoFactorEnabled()) {
                return $this->success([
                    'requires_2fa' => true,
                    'user' => [
                        'id' => $user->id,
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

        Log::warning('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip()
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