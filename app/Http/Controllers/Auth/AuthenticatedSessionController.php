<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        // Frontend'den clinic_code veya company_code gelebilir — normalize et
        $clinicCode = $request->input('clinic_code') ?? $request->input('company_code');

        $throttleKey = Str::lower($request->input('username')).'|'.($clinicCode ?? 'admin').'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['username' => "Çok fazla giriş denemesi. {$seconds} saniye sonra tekrar deneyin."]);
        }

        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = \App\Models\User::where($loginField, $request->username)->first();

        if (! $user || ! \Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['username' => 'Geçersiz kullanıcı adı veya şifre.']);
        }

        if (! $user->is_active) {
            return back()->withErrors(['username' => 'Hesabınız pasif durumdadır.']);
        }

        // Eğer clinic_code YOKSA (Admin Login), Super Admin kontrolü yap
        if (empty($clinicCode)) {
            if (! $user->hasRole('Super Admin')) {
                return back()->withErrors(['username' => 'Bu alana sadece sistem yöneticileri erişebilir.']);
            }
        } else {
            // Eğer clinic_code VARSA, kullanıcının o şirkete ait olduğundan emin ol
            $company = Company::whereRaw('LOWER(code) = ?', [strtolower($clinicCode)])->first();

            if (! $company) {
                return back()->withErrors(['clinic_code' => 'Geçersiz klinik kodu.']);
            }

            if ($user->company_id !== $company->id && ! $user->hasRole('Super Admin')) {
                \Log::warning('Login yetki hatası', [
                    'user_id' => $user->id,
                    'user_company_id' => $user->company_id,
                    'attempted_company_id' => $company->id,
                    'attempted_company_code' => $company->code,
                    'username' => $user->username,
                ]);

                return back()->withErrors([
                    'username' => "Bu klinik için yetkiniz bulunmuyor. (Kullanıcı şirket ID: {$user->company_id}, Klinik şirket ID: {$company->id})",
                ]);
            }
        }

        Auth::login($user, $request->boolean('remember'));
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if ($user->hasRole('Super Admin')) {
            return redirect()->route('admin.companies');
        }

        return redirect()->intended('/');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
