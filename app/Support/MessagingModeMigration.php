<?php

namespace App\Support;

use App\Enums\MessagingMode;
use App\Models\Alter;
use App\Models\Conversation;
use App\Models\Correspondent;
use App\Models\Message;
use App\Models\System;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Migration de mode de messagerie pour un système.
 *
 * Aucune de ces deux opérations n'est parfaite : le split reconstruit une
 * information qui n'a pas été capturée à la réception. Les messages qu'on ne
 * sait pas attribuer sont conservés plutôt que devinés, et reviennent au
 * retour en mode partagé.
 */
class MessagingModeMigration
{
    public function __construct(protected Messaging $messaging) {}

    /**
     * Partagé → perso : une conversation système éclatée en N conversations.
     */
    public function split(System $system): void
    {
        $mine = $this->messaging->systemCorrespondent($system);
        $threshold = $this->messaging->switchThresholdHours($system);

        foreach ($mine->conversations()->with('participants')->get() as $conversation) {
            $other = $conversation->otherParticipant($mine);

            if ($other === null) {
                continue;
            }

            $messages = $conversation->messages()->get();

            foreach ($messages as $message) {
                $message->author_correspondent_id === $mine->getKey()
                    ? $this->splitOutgoing($message, $conversation, $other, $system)
                    : $this->splitIncoming($message, $messages, $conversation, $other, $system, $threshold);
            }

            // La conversation système est vidée : elle ne sert plus qu'à
            // rattacher les messages détachés si le système revient en partagé.
            $conversation->participants()->detach($mine->getKey());
        }
    }

    /**
     * Perso → partagé : N conversations fusionnées en une seule.
     *
     * La fusion n'a lieu que si l'interlocuteur est un correspondant unique.
     * Si l'autre côté est en perso, les N conversations parlent à N alters
     * distincts : ce sont des personnes différentes, pas une seule.
     */
    public function merge(System $system): void
    {
        $mine = $this->messaging->systemCorrespondent($system);
        $alterCorrespondents = $system->alters()->withTrashed()->get()
            ->map(fn (Alter $alter) => $this->messaging->alterCorrespondent($alter));

        $conversationsByOther = collect();

        foreach ($alterCorrespondents as $correspondent) {
            foreach ($correspondent->conversations()->with('participants')->get() as $conversation) {
                $other = $conversation->otherParticipant($correspondent);

                if ($other !== null) {
                    $conversationsByOther->push(['other' => $other, 'conversation' => $conversation]);
                }
            }
        }

        foreach ($conversationsByOther->groupBy(fn ($entry) => $entry['other']->getKey()) as $group) {
            // Le regroupement se fait par interlocuteur : deux alters distincts
            // d'en face restent deux conversations, ce sont deux personnes.
            $other = $group->first()['other'];

            $target = $this->messaging->conversationBetween($mine, $other);

            foreach ($group as $entry) {
                $conversation = $entry['conversation'];

                if ($conversation->is($target)) {
                    continue;
                }

                Message::where('conversation_id', $conversation->getKey())
                    ->update(['conversation_id' => $target->getKey()]);

                $conversation->participants()->detach();
                $conversation->delete();
            }

            // Les messages détachés au split précédent retrouvent leur place.
            Message::whereNull('conversation_id')
                ->whereIn('origin_conversation_id', $group->pluck('conversation.id')->push($target->getKey()))
                ->update(['conversation_id' => $target->getKey()]);

            $this->reattachOrphansOf($mine, $other, $target);
        }
    }

    /** Sortant : suit l'alter qui l'a écrit ; sans auteur, il est conservé hors conversation. */
    protected function splitOutgoing(Message $message, Conversation $origin, Correspondent $other, System $system): void
    {
        if ($message->author_alter_id === null) {
            $this->detach($message, $origin);

            return;
        }

        $alter = Alter::withTrashed()->find($message->author_alter_id);

        $message->update([
            'conversation_id' => $this->conversationFor($alter, $other)->getKey(),
            'origin_conversation_id' => $origin->getKey(),
            'author_correspondent_id' => $this->messaging->alterCorrespondent($alter)->getKey(),
        ]);
    }

    /**
     * Entrant : l'alter destinataire n'a jamais été capturé, on l'approche.
     *
     * 1. même alter avant et après .......... cette conversation (le seuil ne s'applique pas)
     * 2. avant ≠ après, tous deux sous Xh ... dupliqué dans les deux
     * 3. avant seul ......................... conversation de « avant »
     * 4. après seul ......................... conversation de « après »
     * 5. sinon .............................. conservé, hors conversation
     *
     * @param  Collection<int, Message>  $messages
     */
    protected function splitIncoming(
        Message $message,
        Collection $messages,
        Conversation $origin,
        Correspondent $other,
        System $system,
        int $threshold,
    ): void {
        $before = $this->neighbourAlter($message, $messages, $origin, 'before', $threshold);
        $after = $this->neighbourAlter($message, $messages, $origin, 'after', $threshold);

        // Règle 1 : même alter des deux côtés, le seuil est ignoré.
        $sameAlter = $this->neighbourAlter($message, $messages, $origin, 'before', PHP_INT_MAX);
        $sameAlterAfter = $this->neighbourAlter($message, $messages, $origin, 'after', PHP_INT_MAX);

        if ($sameAlter !== null && $sameAlter === $sameAlterAfter) {
            $this->attach($message, $sameAlter, $other, $origin);

            return;
        }

        if ($before !== null && $after !== null) {
            // Règle 2 : le contexte est ambigu, les deux conversations le reçoivent.
            $this->attach($message, $before, $other, $origin);
            $this->duplicate($message, $after, $other, $origin);

            return;
        }

        $target = $before ?? $after;

        $target === null
            ? $this->detach($message, $origin)                       // règle 5
            : $this->attach($message, $target, $other, $origin);     // règles 3 et 4
    }

    /**
     * Alter du système ayant écrit juste avant ou juste après, dans la limite du seuil.
     *
     * @param  Collection<int, Message>  $messages
     */
    protected function neighbourAlter(
        Message $message,
        Collection $messages,
        Conversation $origin,
        string $direction,
        int $thresholdHours,
    ): ?int {
        $candidates = $messages
            ->filter(fn (Message $other) => $other->author_alter_id !== null)
            ->filter(fn (Message $other) => $direction === 'before'
                ? $other->created_at < $message->created_at
                : $other->created_at > $message->created_at)
            ->sortBy('created_at');

        $neighbour = $direction === 'before' ? $candidates->last() : $candidates->first();

        if ($neighbour === null) {
            return null;
        }

        $gap = $neighbour->created_at->diffInHours($message->created_at, absolute: true);

        return $gap < $thresholdHours ? $neighbour->author_alter_id : null;
    }

    protected function attach(Message $message, int $alterId, Correspondent $other, Conversation $origin): void
    {
        $alter = Alter::withTrashed()->find($alterId);

        $message->update([
            'conversation_id' => $this->conversationFor($alter, $other)->getKey(),
            'origin_conversation_id' => $origin->getKey(),
        ]);
    }

    protected function duplicate(Message $message, int $alterId, Correspondent $other, Conversation $origin): void
    {
        $alter = Alter::withTrashed()->find($alterId);

        $copy = $message->replicate(['uuid']);
        $copy->uuid = null;
        $copy->conversation_id = $this->conversationFor($alter, $other)->getKey();
        $copy->origin_conversation_id = $origin->getKey();
        $copy->save();
    }

    /** Conservé en base, rattaché à aucune conversation. */
    protected function detach(Message $message, Conversation $origin): void
    {
        $message->update([
            'conversation_id' => null,
            'origin_conversation_id' => $origin->getKey(),
        ]);
    }

    protected function conversationFor(Alter $alter, Correspondent $other): Conversation
    {
        return $this->messaging->conversationBetween(
            $this->messaging->alterCorrespondent($alter),
            $other,
        );
    }

    protected function reattachOrphansOf(Correspondent $mine, Correspondent $other, Conversation $target): void
    {
        DB::table('messages')
            ->whereNull('conversation_id')
            ->whereIn('origin_conversation_id', function ($query) use ($mine, $other) {
                $query->select('conversation_id')
                    ->from('conversation_participants')
                    ->whereIn('correspondent_id', [$mine->getKey(), $other->getKey()]);
            })
            ->update(['conversation_id' => $target->getKey()]);
    }

    /** Applique le changement de mode demandé par un système. */
    public function apply(System $system, MessagingMode $from, MessagingMode $to): void
    {
        if ($from === $to) {
            return;
        }

        $to === MessagingMode::Personal ? $this->split($system) : $this->merge($system);
    }
}
