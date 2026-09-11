<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\System;
use App\Support\Front;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Second facteur. Tant qu'il n'est pas fourni, la session reste anonyme :
 * seul l'identifiant du système en attente est gardé, le temps du challenge.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(protected TwoFactor $totp) {}

    public function create(Request $request): Response|RedirectResponse
    {
        return $this->pending($request) === null
            ? redirect()->route('login')
            : Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request, Front $front): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $system = $this->pending($request);

        if ($system === null) {
            return redirect()->route('login');
        }

        $key = 'two-factor|'.$system->getKey().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, AuthenticatedSessionController::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => __('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($key),
                    'minutes' => ceil(RateLimiter::availableIn($key) / 60),
                ]),
            ])->status(429);
        }

        $passed = match (true) {
            filled($data['code'] ?? null) => $this->totp->verify($system->two_factor_secret, $data['code']),
            filled($data['recovery_code'] ?? null) => $system->consumeRecoveryCode($data['recovery_code']),
            default => false,
        };

        if (! $passed) {
            RateLimiter::hit($key, AuthenticatedSessionController::DECAY_SECONDS);

            throw ValidationException::withMessages(['code' => 'Code invalide.']);
        }

        RateLimiter::clear($key);

        $remember = (bool) ($request->session()->get(AuthenticatedSessionController::PENDING_KEY)['remember'] ?? false);
        $request->session()->forget(AuthenticatedSessionController::PENDING_KEY);

        Auth::login($system, $remember);
        $request->session()->regenerate();
        $front->ensure();

        return redirect()->intended(route('dashboard'));
    }

    protected function pending(Request $request): ?System
    {
        $id = $request->session()->get(AuthenticatedSessionController::PENDING_KEY)['id'] ?? null;

        return $id === null ? null : System::find($id);
    }
}
