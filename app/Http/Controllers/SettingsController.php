<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Support\Front;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paramètres, en deux onglets : ce qui appartient au système (compte, sécurité,
 * messagerie) et ce qui appartient à l'alter au front (profil, blocages).
 */
class SettingsController extends Controller
{
    public function __construct(protected Front $front) {}

    public function index(Request $request): Response
    {
        $active = $this->front->ensure();

        return Inertia::render('Settings/Index', [
            'alter' => $active ? (new AlterResource($active))->resolve() : null,
            'alters' => AlterResource::collection($request->user()->alters()->orderBy('name')->get()),
            'email' => $request->user()->email,
            'verified' => $request->user()->hasVerifiedEmail(),
        ]);
    }
}
