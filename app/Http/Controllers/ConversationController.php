<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\Alter;
use App\Models\Conversation;
use App\Models\Correspondent;
use App\Models\System;
use App\Support\BlockList;
use App\Support\Front;
use App\Support\Messaging;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(
        protected Front $front,
        protected Messaging $messaging,
        protected BlockList $blocks,
    ) {}

    public function index(): Response
    {
        $mine = $this->messaging->correspondentFor($this->front->currentOrFail());

        $conversations = $mine->conversations()
            ->with('participants')
            ->latest('conversations.updated_at')
            ->get();

        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations->map(fn (Conversation $conversation) => [
                'id' => $conversation->uuid,
                // En mode partagé, le titre est le nom du système d'en face,
                // jamais celui d'un de ses alters.
                'title' => $conversation->otherParticipant($mine)?->displayName() ?? 'Inconnu',
                'mode' => $conversation->effective_mode->value,
                'updated_at' => $conversation->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    public function show(Conversation $conversation): Response
    {
        $alter = $this->front->currentOrFail();
        $mine = $this->messaging->correspondentFor($alter);

        $this->authorizeParticipation($conversation, $mine);

        $other = $conversation->otherParticipant($mine);
        $showsAuthor = $this->showsAuthorFor($other);

        return Inertia::render('Conversations/Show', [
            'conversation' => [
                'id' => $conversation->uuid,
                'title' => $other?->displayName() ?? 'Inconnu',
                'mode' => $conversation->effective_mode->value,
            ],
            'messages' => $conversation->messages()->with(['author', 'authorAlter'])->get()
                ->map(fn ($message) => (new MessageResource(
                    $message,
                    $message->author_correspondent_id === $mine->getKey()
                        ? $this->messaging->showsAuthor($alter->system)
                        : $showsAuthor,
                ))->resolve()),
            'myCorrespondentId' => $mine->getKey(),
        ]);
    }

    /** Ouvre (ou retrouve) la conversation avec un alter. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['handle' => ['required', 'string']]);

        $alter = $this->front->currentOrFail();
        $target = Alter::query()->where('handle', $data['handle'])->firstOrFail();

        abort_if($target->is($alter), 422, 'Impossible de se parler à soi-même.');
        abort_if($this->blocks->blocks($alter, $target), 404);

        $conversation = $this->messaging->conversationBetween(
            $this->messaging->correspondentFor($alter),
            $this->messaging->correspondentFor($target),
        );

        return redirect()->route('conversations.show', $conversation);
    }

    protected function authorizeParticipation(Conversation $conversation, Correspondent $mine): void
    {
        abort_unless($conversation->participants->contains($mine), 404);
    }

    /** Le système d'en face montre-t-il l'auteur de ses messages ? */
    protected function showsAuthorFor(?Correspondent $other): bool
    {
        $subject = $other?->subject();

        return match (true) {
            $subject instanceof Alter => true,
            $subject instanceof System => $this->messaging->showsAuthor($subject),
            default => false,
        };
    }
}
