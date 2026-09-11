<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\System;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredSystemController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:systems,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // `create()` ne rapporte que les colonnes écrites : on relit la ligne
        // pour disposer de l'état complet (vérification, second facteur).
        $system = System::create($data)->refresh();

        event(new Registered($system));
        Auth::login($system);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('status', 'Système créé. Vérifiez votre adresse pour ouvrir le reste du compte.');
    }
}
