<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Turning 2FA on and off from the profile page. */
class TwoFactorController extends Controller
{
    /** Step 1: create a secret; the profile page then shows the QR code. */
    public function enable(Request $request, TwoFactorService $twoFactor)
    {
        $request->validateWithBag('twoFactor', ['current_password' => ['required', 'current_password']], ProfileController::messages());
        $request->user()->forceFill([
            'two_factor_secret' => $twoFactor->generateSecret(),
            'two_factor_recovery_codes' => $twoFactor->generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->to(route('profile.show').'#securite')->with('status', 'Scannez le QR code puis saisissez le code affiché par l’application.');
    }

    /** Step 2: the user proves the app works by typing a code. */
    public function confirm(Request $request, TwoFactorService $twoFactor)
    {
        $request->validateWithBag('twoFactor', ['code' => ['required', 'string']], ProfileController::messages());
        $user = $request->user();

        if (! $user->two_factor_secret || ! $twoFactor->verify($user->two_factor_secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'Code incorrect. Vérifiez l’heure de votre téléphone et réessayez.'])->errorBag('twoFactor');
        }
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return redirect()->to(route('profile.show').'#securite')->with('status', 'Double authentification activée. Conservez vos codes de récupération.')->with('showRecoveryCodes', true);
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorService $twoFactor)
    {
        $request->validateWithBag('twoFactor', ['current_password' => ['required', 'current_password']], ProfileController::messages());
        $request->user()->forceFill(['two_factor_recovery_codes' => $twoFactor->generateRecoveryCodes()])->save();

        return redirect()->to(route('profile.show').'#securite')->with('status', 'Nouveaux codes de récupération générés. Les anciens ne fonctionnent plus.')->with('showRecoveryCodes', true);
    }

    public function disable(Request $request)
    {
        $request->validateWithBag('twoFactor', ['current_password' => ['required', 'current_password']], ProfileController::messages());
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();

        return redirect()->to(route('profile.show').'#securite')->with('status', 'Double authentification désactivée.');
    }
}
