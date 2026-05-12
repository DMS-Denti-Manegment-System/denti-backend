<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthPageController extends Controller
{
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
            $invitation = UserInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            if ($invitation->accepted_at || $invitation->isExpired()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'token' => 'Davet gecersiz veya suresi dolmus.',
                ]);
            }

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $invitation->email,
                'password' => Hash::make($request->input('password')),
            ]);

            $user->assignRole($invitation->role);
            $invitation->update(['accepted_at' => now()]);
        });

        return redirect()->route('login')->with('status', 'Hesabiniz olusturuldu. Artik giris yapabilirsiniz.');
    }
}
