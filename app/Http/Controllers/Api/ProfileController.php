<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileInfoRequest;
use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use JsonResponseTrait;

    /**
     * Update the user's profile information.
     */
    public function updateInfo(UpdateProfileInfoRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $user->update($request->validated());

        return $this->success($user, 'Profile information updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(UpdateProfilePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('The provided password does not match your current password.', 422, [
                'current_password' => ['The provided password does not match your current password.']
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Password updated successfully.');
    }
}
