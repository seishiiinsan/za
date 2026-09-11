<?php

namespace App\Support;

use App\Enums\MessagingMode;
use App\Models\Alter;
use App\Models\Conversation;
use App\Models\Correspondent;
use App\Models\Message;
use App\Models\System;
use Illuminate\Support\Collection;

/**
 * Résolution des correspondants et des conversations.
 *
 * Le mode d'un système dit comment il s'expose ; le mode effectif d'une
 * conversation se négocie entre les deux côtés et reste figé dessus.
 */
class Messaging
{
    /** Le correspondant par lequel cet alter s'adresse au monde. */
    public function correspondentFor(Alter $alter): Correspondent
    {
        $system = $alter->system;

        return $this->modeOf($system) === MessagingMode::Shared
            ? $this->systemCorrespondent($system)
            : $this->alterCorrespondent($alter);
    }

    public function alterCorrespondent(Alter $alter): Correspondent
    {
        return Correspondent::firstOrCreate([
            'type' => Correspondent::TYPE_ALTER,
            'ref_id' => $alter->getKey(),
        ]);
    }

    public function systemCorrespondent(System $system): Correspondent
    {
        return Correspondent::firstOrCreate([
            'type' => Correspondent::TYPE_SYSTEM,
            'ref_id' => $system->getKey(),
        ]);
    }

    public function modeOf(System $system): MessagingMode
    {
        return MessagingMode::tryFrom($system->settings['messaging_mode'] ?? '')
            ?? MessagingMode::Personal;
    }

    /** Seuil de switch, en heures : au-delà, on n'attribue plus un message entrant. */
    public function switchThresholdHours(System $system): int
    {
        return max(1, (int) ($system->settings['switch_threshold_hours'] ?? 5));
    }

    /** Le système montre-t-il quel alter a écrit chaque message ? */
    public function showsAuthor(System $system): bool
    {
        return (bool) ($system->settings['show_message_author'] ?? false);
    }

    /**
     * Conversation entre deux correspondants, créée au besoin.
     *
     * Le mode effectif est partagé dès qu'un des deux côtés s'expose en système :
     * il ne découle pas du seul réglage de celui qui écrit.
     */
    public function conversationBetween(Correspondent $mine, Correspondent $theirs): Conversation
    {
        $existing = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->whereKey($mine->getKey()))
            ->whereHas('participants', fn ($query) => $query->whereKey($theirs->getKey()))
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = Conversation::create([
            'effective_mode' => $mine->isAlter() && $theirs->isAlter()
                ? MessagingMode::Personal
                : MessagingMode::Shared,
        ]);

        $conversation->participants()->attach([$mine->getKey(), $theirs->getKey()]);

        return $conversation->load('participants');
    }

    /** Écrit un message au nom du front actif, par le correspondant de son système. */
    public function send(Conversation $conversation, Alter $author, string $content): Message
    {
        $message = new Message([
            'content' => $content,
            'author_correspondent_id' => $this->correspondentFor($author)->getKey(),
            'author_alter_id' => $author->getKey(),
        ]);

        $conversation->messages()->save($message);
        $conversation->touch();

        return $message;
    }

    /** Correspondants par lesquels ce système reçoit (tous ses alters, ou lui-même). */
    public function correspondentsOf(System $system): Collection
    {
        if ($this->modeOf($system) === MessagingMode::Shared) {
            return collect([$this->systemCorrespondent($system)]);
        }

        return $system->alters->map(fn (Alter $alter) => $this->alterCorrespondent($alter));
    }
}
