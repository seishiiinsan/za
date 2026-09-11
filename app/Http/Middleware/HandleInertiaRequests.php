<?php

namespace App\Http\Middleware;

use App\Http\Controllers\NotificationController;
use App\Http\Resources\AlterResource;
use App\Support\Front;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(protected Front $front, protected Notifier $notifier) {}

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
            'notifications' => function () {
                $alter = $this->front->current();

                if ($alter === null) {
                    return ['unread' => 0, 'items' => []];
                }

                $items = $this->notifier->visibleTo($alter)->limit(12)->get();

                return [
                    'unread' => $items->whereNull('read_at')->count(),
                    'items' => NotificationController::present($items, $alter),
                ];
            },
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
