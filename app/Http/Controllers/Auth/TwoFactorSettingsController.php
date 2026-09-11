<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorSettingsController extends Controller
{
    public function __construct(protected TwoFactor $totp) {}

    public function edit(Request $request): Response
    {
        $system = $request->user();

        return Inertia::render('Settings/Security', [
            'enabled' => $system->hasTwoFactorEnabled(),
            'pending' => $system->two_factor_secret !== null && ! $system->hasTwoFactorEnabled(),
            'secret' => $system->hasTwoFactorEnabled() ? null : $system->two_factor_secret,
            'provisioningUri' => $system->two_factor_secret === null || $system->hasTwoFactorEnabled()
                ? null
                : $this->totp->provisioningUri($system->two_factor_secret, $system->email, config('app.name')),
            'recoveryCodes' => $system->hasTwoFactorEnabled() ? $system->two_factor_recovery_codes : null,
            'verified' => $system->hasVerifiedEmail(),
        ]);
    }

    /** Prépare un secret. La double authentification n'est active qu'après confirmation. */
    public function store(Request $request): RedirectResponse
    {
        // `forceFill` : ces colonnes ne sont pas assignables en masse, elles ne
        // doivent jamais suivre une entrée utilisateur.
        $request->user()->forceFill([
            'two_factor_secret' => $this->totp->generateSecret(),
            'two_factor_recovery_codes' => $this->totp->generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'Scannez le secret dans votre application, puis confirmez avec un code.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $system = $request->user();

        abort_if($system->two_factor_secret === null, 409);

        if (! $this->totp->verify($system->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Code invalide.']);
        }

        $system->forceFill(['two_factor_confirmed_at' => now()])->save();

        return back()->with('status', 'Double authentification active. Gardez vos codes de secours.');
    }

    /** Désactiver exige le mot de passe : une session volée ne suffit pas. */
    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        $system = $request->user();

        if (! Hash::check($data['password'], $system->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }

        $system->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'Double authentification désactivée.');
    }

    public function recoveryCodes(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasTwoFactorEnabled(), 409);

        $request->user()->forceFill([
            'two_factor_recovery_codes' => $this->totp->generateRecoveryCodes(),
        ])->save();

        return back()->with('status', 'Nouveaux codes de secours générés.');
    }
}
