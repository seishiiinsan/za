<?php

namespace App\Support;

use App\Models\Alter;
use App\Models\AlterNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Notifications au niveau alter.
 *
 * Une notification appartient toujours à un alter. Elle peut remonter au
 * dashboard système et être déléguée à un autre alter du même système : ces
 * deux chemins restent privés, ils ne créent aucun lien public entre alters.
 */
class Notifier
{
    /** @param array<string, mixed> $payload */
    public function notify(Alter $recipient, string $type, array $payload = []): ?AlterNotification
    {
        return AlterNotification::create([
            'alter_id' => $recipient->getKey(),
            'type' => $type,
            'payload' => $payload,
        ]);
    }

    /** Notifie chaque auteur d'un post, sauf celui qui déclenche l'événement. */
    public function notifyAuthors(iterable $authors, Alter $actor, string $type, array $payload = []): void
    {
        foreach ($authors as $author) {
            if (! $author->is($actor) && ! $author->trashed()) {
                $this->notify($author, $type, $payload);
            }
        }
    }

    /**
     * Notifications visibles par un alter : les siennes, plus celles qu'un
     * autre alter du même système lui a déléguées.
     *
     * @return Builder<AlterNotification>
     */
    public function visibleTo(Alter $alter)
    {
        $delegated = Alter::query()
            ->where('system_id', $alter->system_id)
            ->whereKeyNot($alter->getKey())
            ->get()
            ->filter(fn (Alter $other) => $other->delegate()?->is($alter) ?? false)
            ->modelKeys();

        return AlterNotification::query()
            ->with('alter')
            ->whereIn('alter_id', [$alter->getKey(), ...$delegated])
            ->latest('id');
    }

    /**
     * Notifications remontées au dashboard : celles des alters qui l'ont autorisé.
     *
     * @param  Collection<int, Alter>  $alters
     * @return Builder<AlterNotification>
     */
    public function escalatedTo($alters)
    {
        $ids = $alters->filter(fn (Alter $alter) => $alter->notifiesSystem())->modelKeys();

        return AlterNotification::query()->with('alter')->whereIn('alter_id', $ids)->latest('id');
    }
}
