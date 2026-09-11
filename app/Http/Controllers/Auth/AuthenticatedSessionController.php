<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\System;
use App\Support\Front;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /** Session : le système en attente de son second facteur. */
    public const PENDING_KEY = 'auth.two_factor_pending';

    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request, Front $front): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $system = System::where('email', $credentials['email'])->first();

        if ($system === null || ! Hash::check($credentials['password'], $system->password)) {
            RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Second facteur : le mot de passe seul n'ouvre pas la session.
        if ($system->hasTwoFactorEnabled()) {
            $request->session()->put(self::PENDING_KEY, [
                'id' => $system->getKey(),
                'remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($system, $request->boolean('remember'));
        $request->session()->regenerate();
        $front->ensure();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, Front $front): RedirectResponse
    {
        $front->clear();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Limite les tentatives par couple e-mail + IP.
     *
     * La clé inclut l'e-mail : sans cela, une IP partagée bloquerait des
     * systèmes qui n'ont rien demandé.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ])->status(429);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
