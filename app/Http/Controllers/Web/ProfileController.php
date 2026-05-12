<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Http\Requests\UpdateProfileInfoRequest;
use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use HandlesOperationsResponses;

    public function index(): View
    {
        $user = auth()->user()->load(['clinic', 'roles']);
        return view('operations.profile.index', compact('user'));
    }

    public function updateInfo(UpdateProfileInfoRequest $request): RedirectResponse
    {
        auth()->user()->update($request->validated());
        return redirect()->route('profile.index')->with('status', 'Profil bilgileri güncellendi.');
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.index')->with('status', 'Şifre güncellendi.');
    }

    public function generate2fa(Request $request): JsonResponse
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret(auth()->user());
        $qrUrl = $service->getQrCodeUrl(auth()->user());

        return response()->json(['secret' => $secret, 'qrUrl' => $qrUrl]);
    }

    public function confirm2fa(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);
        $success = app(TwoFactorService::class)->confirm2FA(auth()->user(), $request->code);

        return response()->json([
            'success' => $success,
            'message' => $success ? '2FA doğrulandı ve aktif edildi.' : 'Geçersiz kod.',
            'recoveryCodes' => $success ? auth()->user()->two_factor_recovery_codes : [],
        ]);
    }

    public function disable2fa(Request $request): RedirectResponse|JsonResponse
    {
        auth()->user()->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return $this->actionResponse($request, 'profile.index', '2FA devre dışı bırakıldı.');
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $codes = app(TwoFactorService::class)->generateRecoveryCodes(auth()->user());
        return response()->json(['recoveryCodes' => $codes]);
    }
}
