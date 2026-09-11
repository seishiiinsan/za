<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Models\Alter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil public d'un alter. Surface la plus sensible : aucune donnée système,
 * et rien ne doit permettre de deviner les autres alters du même système.
 */
class AlterProfileController extends Controller
{
    public function show(string $handle): Response
    {
        $alter = Alter::query()->where('handle', $handle)->firstOrFail();

        return Inertia::render('Profile/Show', [
            'alter' => new AlterResource($alter),
        ]);
    }
}
