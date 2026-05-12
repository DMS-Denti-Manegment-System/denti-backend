<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Confirm2FARequest;
use App\Http\Requests\Auth\Verify2FARequest;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TwoFactorAuthController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Generate 2FA Secret and QR Code.
     */
    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();

        $secret = $this->twoFactorService->generateSecret($user);
        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user);

        return $this->success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ], '2FA setup generated');
    }

    /**
     * Confirm and enable 2FA.
     */
    public function confirm(Confirm2FARequest $request): JsonResponse
    {
        $user = $request->user();

        if ($this->twoFactorService->confirm2FA($user, $request->code)) {
            return $this->success([
                'recovery_codes' => $user->two_factor_recovery_codes,
            ], '2FA enabled successfully. Please save your recovery codes.');
        }

        Log::warning('Failed 2FA confirmation attempt', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return $this->error('Invalid verification code', 422);
    }

    /**
     * Verify 2FA code during login.
     */
    public function verify(Verify2FARequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->error('Unauthorized', 401);
        }

        $code = $request->code;
        $isVerified = false;

        // Check if it's a TOTP code (usually 6 digits) or recovery code
        if (strlen($code) === 6 && is_numeric($code)) {
            $isVerified = $this->twoFactorService->verifyCode($user, $code);
        } else {
            $isVerified = $this->twoFactorService->verifyRecoveryCode($user, $code);
        }

        if ($isVerified) {
            $request->session()->put('2fa_verified', true);
            $user->load(['roles']);

            return $this->success([
                'user' => $user,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ], '2FA verification successful');
        }

        Log::warning('Failed 2FA verification attempt', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'code_type' => (strlen($code) === 6 && is_numeric($code)) ? 'totp' : 'recovery',
        ]);

        return $this->error('Invalid verification code', 422);
    }

    /**
     * Regenerate Recovery Codes.
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->two_factor_confirmed_at) {
            return $this->error('2FA must be enabled first', 400);
        }

        $codes = $this->twoFactorService->generateRecoveryCodes($user);

        return $this->success([
            'recovery_codes' => $codes,
        ], 'Recovery codes regenerated successfully');
    }
}
