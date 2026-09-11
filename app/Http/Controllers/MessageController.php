<?php

namespace App\Http\Controllers;

use App\Models\Alter;
use App\Models\AlterNotification;
use App\Models\Conversation;
use App\Models\Correspondent;
use App\Models\System;
use App\Support\Front;
use App\Support\Messaging;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        protected Front $front,
        protected Messaging $messaging,
        protected Notifier $notifier,
    ) {}

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        $alter = $this->front->currentOrFail();
        $mine = $this->messaging->correspondentFor($alter);

        abort_unless($conversation->participants->contains($mine), 404);

        $this->messaging->send($conversation, $alter, $data['content']);
        $this->notifyRecipients($conversation, $mine, $alter);

        return back();
    }

    /**
     * Notifie l'autre côté. En messagerie partagée, chaque alter du système
     * destinataire reçoit la notification : c'est une boîte commune.
     */
    protected function notifyRecipients(Conversation $conversation, Correspondent $mine, Alter $author): void
    {
        $other = $conversation->otherParticipant($mine);
        $subject = $other?->subject();

        $recipients = match (true) {
            $subject instanceof Alter => collect([$subject]),
            $subject instanceof System => $subject->alters,
            default => collect(),
        };

        foreach ($recipients as $recipient) {
            $this->notifier->notify($recipient, AlterNotification::TYPE_MESSAGE, [
                // Le nom affiché est celui du correspondant, pas celui de
                // l'alter qui écrit : la surface système reste entière.
                'actor' => $this->messaging->correspondentFor($author)->displayName(),
                'conversation' => $conversation->uuid,
            ]);
        }
    }
}
