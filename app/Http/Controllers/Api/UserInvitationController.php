<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Mail\UserInvitationMail;
use App\Models\Company;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserInvitationController extends Controller
{
    /**
     * Invite a new user to the company.
     */
    public function invite(StoreInvitationRequest $request): JsonResponse
    {
        $user = Auth::user();
        try {
            $invitation = DB::transaction(function () use ($request, $user) {
                $company = Company::whereKey($user->company_id)->lockForUpdate()->firstOrFail();

                if ($company->users()->count() + $company->invitations()->whereNull('accepted_at')->count() >= $company->max_users) {
                    throw new \RuntimeException('Maximum user limit reached for your subscription plan.');
                }

                return UserInvitation::create([
                    'email' => $request->email,
                    'company_id' => $company->id,
                    'role' => $request->role,
                    'token' => Str::random(40),
                    'expires_at' => now()->addHours(24),
                ]);
            });
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $inviteUrl = config('app.frontend_url').'/accept-invitation/'.$invitation->token;

        Mail::to($request->email)->send(new UserInvitationMail($invitation, $inviteUrl));

        return $this->success($invitation, 'Invitation sent successfully.');
    }

    /**
     * Accept an invitation and create a user.
     */
    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $invitation = UserInvitation::where('token', $request->token)
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return $this->error('Invitation is invalid or has expired.', 422);
        }

        return DB::transaction(function () use ($request, $invitation) {
            $invitation = UserInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            if ($invitation->accepted_at || $invitation->isExpired()) {
                return $this->error('Invitation is invalid or has expired.', 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
                'company_id' => $invitation->company_id,
            ]);

            setPermissionsTeamId($invitation->company_id);
            $user->assignRole($invitation->role);

            $invitation->update(['accepted_at' => now()]);

            return $this->success($user, 'Account created successfully. You can now login.');
        });
    }
}
