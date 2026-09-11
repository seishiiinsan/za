<?php

namespace App\Http\Middleware;

use App\Http\Resources\AlterResource;
use App\Support\Front;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(protected Front $front) {}

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $system = $request->user();

        return array_merge(parent::share($request), [
            // Volontairement : aucun identifiant système partagé côté client.
            'auth' => [
                'authenticated' => $system !== null,
                'email' => $system?->email,
                'alters' => $system
                    ? AlterResource::collection($system->alters()->orderBy('name')->get())->resolve()
                    : [],
                'activeAlter' => ($alter = $this->front->current())
                    ? (new AlterResource($alter))->resolve()
                    : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
