<?php

namespace App\Http\Controllers;

use App\Models\Alter;
use App\Support\Front;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switch de front : change l'alter actif sans toucher à l'authentification.
 */
class FrontController extends Controller
{
    public function update(Request $request, Front $front): RedirectResponse
    {
        $data = $request->validate([
            'alter_id' => ['required', 'uuid'],
        ]);

        /** @var Alter|null $alter */
        $alter = $request->user()->alters()->where('uuid', $data['alter_id'])->first();

        abort_if($alter === null, 404);

        $front->set($alter);

        return back()->with('status', "Front actif : {$alter->name}.");
    }
}
