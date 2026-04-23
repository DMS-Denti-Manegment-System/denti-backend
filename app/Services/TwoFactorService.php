<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TwoFactorService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate 2FA secret for a user.
     */
    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null, // Reset confirmation if re-generating
        ]);

        return $secret;
    }

    /**
     * Get QR Code URL for the user.
     */
    public function getQrCodeUrl(User $user): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );
    }

    /**
     * Verify TOTP code.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($user->two_factor_secret, $code);
    }

    /**
     * Confirm 2FA for a user.
     */
    public function confirm2FA(User $user, string $code): bool
    {
        if ($this->verifyCode($user, $code)) {
            $user->update([
                'two_factor_confirmed_at' => now(),
            ]);

            // Generate initial recovery codes if they don't exist
            if (!$user->two_factor_recovery_codes) {
                $this->generateRecoveryCodes($user);
            }

            return true;
        }

        return false;
    }

    /**
     * Generate recovery codes for a user.
     */
    public function generateRecoveryCodes(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }

        $user->update([
            'two_factor_recovery_codes' => $codes, // Model cast will handle encryption
        ]);

        return $codes;
    }

    /**
     * Verify and use a recovery code.
     *
     * Race condition önlemi: Ayni recovery code'un eşzamanli iki istekte
     * geçerli sayilmamasi icin DB::transaction + lockForUpdate kullanilir.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            // Satiri kilitle: baska bir islem ayni anda bu kullanicinin kodlarini degistiremez
            $freshUser = User::lockForUpdate()->find($user->id);

            if (!$freshUser) {
                return false;
            }

            $codes = $freshUser->two_factor_recovery_codes ?? [];

            if (($key = array_search($code, $codes)) !== false) {
                unset($codes[$key]);

                $freshUser->update([
                    'two_factor_recovery_codes' => array_values($codes),
                ]);

                return true;
            }

            return false;
        });
    }
}
