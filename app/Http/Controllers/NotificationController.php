<?php

namespace App\Http\Controllers;

use App\Models\Alter;
use App\Models\AlterNotification;
use App\Support\Front;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(protected Front $front, protected Notifier $notifier) {}

    public function index(): Response
    {
        $alter = $this->front->currentOrFail();

        return Inertia::render('Notifications/Index', [
            'notifications' => $this->present($this->notifier->visibleTo($alter)->limit(50)->get(), $alter),
        ]);
    }

    /** Ouvrir la cloche vaut lecture : tout ce qui est affiché passe en lu. */
    public function readAll(): RedirectResponse
    {
        $alter = $this->front->currentOrFail();

        $this->notifier->visibleTo($alter)->whereNull('read_at')->get()
            ->each(fn (AlterNotification $notification) => $this->notifier->markRead($notification));

        return back();
    }

    /** Retirer une notification de l'affichage. */
    public function destroy(Request $request, AlterNotification $notification): RedirectResponse
    {
        $alterIds = $request->user()->alters()->pluck('id');

        abort_unless($alterIds->contains($notification->alter_id), 404);

        $notification->delete();

        return back();
    }

    public function read(Request $request, AlterNotification $notification): RedirectResponse
    {
        $alterIds = $request->user()->alters()->pluck('id');

        abort_unless($alterIds->contains($notification->alter_id), 404);

        $this->notifier->markRead($notification);

        return back();
    }

    /**
     * @param  Collection<int, AlterNotification>  $notifications
     * @return array<int, array<string, mixed>>
     */
    public static function present($notifications, ?Alter $viewer = null): array
    {
        return $notifications->map(fn (AlterNotification $notification) => [
            'id' => $notification->uuid,
            'type' => $notification->type,
            'payload' => $notification->payload,
            'read' => $notification->read_at !== null,
            'shared' => $notification->group_uuid !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
            'for' => $notification->alter->name,
            // Une notification déléguée reste identifiée comme telle : on sait
            // toujours pour quel alter on traite.
            'delegated' => $viewer !== null && $notification->alter_id !== $viewer->getKey(),
        ])->all();
    }
}
