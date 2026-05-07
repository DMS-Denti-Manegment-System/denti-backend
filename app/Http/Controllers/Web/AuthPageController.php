<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthPageController extends Controller
{
    public function adminLoginForm(): View
    {
        return view('auth.admin-login');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $throttleKey = 'admin_login|'.Str::lower($request->input('username')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['username' => "Cok fazla giris denemesi. {$seconds} saniye sonra tekrar deneyin."]);
        }

        $user = User::where('username', $request->input('username'))->first();

        if (! $user || ! $user->hasRole(User::ROLE_SUPER_ADMIN) || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['username' => 'Gecersiz kullanici adi veya sifre.'])->onlyInput('username');
        }

        if (! $user->is_active) {
            return back()->withErrors(['username' => 'Hesabiniz pasif durumdadir.'])->onlyInput('username');
        }

        Auth::login($user, $request->boolean('remember'));
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function invitationForm(string $token): View
    {
        $invitation = UserInvitation::where('token', $token)->first();

        abort_if(! $invitation || $invitation->isExpired() || $invitation->accepted_at, 404);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
        ]);
    }

    public function acceptInvitation(AcceptInvitationRequest $request): RedirectResponse
    {
        $invitation = UserInvitation::where('token', $request->input('token'))
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return back()->withErrors(['token' => 'Davet gecersiz veya suresi dolmus.']);
        }

        DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $invitation->email,
                'password' => Hash::make($request->input('password')),
                'company_id' => $invitation->company_id,
            ]);

            $user->assignRole($invitation->role);
            $invitation->update(['accepted_at' => now()]);
        });

        return redirect()->route('login')->with('status', 'Hesabiniz olusturuldu. Artik giris yapabilirsiniz.');
    }
}
