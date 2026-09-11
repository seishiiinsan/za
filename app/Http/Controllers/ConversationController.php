<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Http\Resources\MessageResource;
use App\Models\Alter;
use App\Models\Conversation;
use App\Models\Correspondent;
use App\Models\System;
use App\Support\BlockList;
use App\Support\Front;
use App\Support\Handles;
use App\Support\Messaging;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(
        protected Front $front,
        protected Messaging $messaging,
        protected BlockList $blocks,
        protected Handles $handles,
    ) {}

    public function index(): Response
    {
        $alter = $this->front->currentOrFail();
        $mine = $this->messaging->correspondentFor($alter);

        $conversations = $mine->conversations()
            ->with([
                'participants',
                // L'auteur vient avec : l'aperçu le nomme, et le chargement
                // tardif est refusé.
                'messages' => fn ($query) => $query->reorder()->latest('id')->limit(1)->with('author'),
            ])
            ->latest('conversations.updated_at')
            ->get();

        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations->map(function (Conversation $conversation) use ($mine) {
                $last = $conversation->messages->first();
                $readAt = $conversation->participants->firstWhere('id', $mine->getKey())?->pivot?->last_read_at;

                return [
                    'id' => $conversation->uuid,
                    // En mode partagé, le titre est le nom du système d'en face,
                    // jamais celui d'un de ses alters.
                    'title' => $conversation->otherParticipant($mine)?->displayName() ?? 'Inconnu',
                    'mode' => $conversation->effective_mode->value,
                    'last_message' => $last === null ? null : [
                        'excerpt' => Str::limit($last->content, 70),
                        'author' => $last->author_correspondent_id === $mine->getKey()
                            ? 'Toi'
                            : $last->author->displayName(),
                        'mine' => $last->author_correspondent_id === $mine->getKey(),
                        'at' => $last->created_at?->toIso8601String(),
                    ],
                    // Non lue : un message reçu après ma dernière ouverture.
                    'unread' => $last !== null
                        && $last->author_correspondent_id !== $mine->getKey()
                        && ($readAt === null || $last->created_at->greaterThan($readAt)),
                ];
            }),
            // Le sélecteur s'ouvre sur les gens que l'alter connaît déjà.
            'contacts' => AlterResource::collection(
                $alter->following()->wherePivot('accepted', true)->orderBy('name')->get()
                    ->concat($alter->followers()->wherePivot('accepted', true)->orderBy('name')->get())
                    ->unique('id')
                    ->values()
            ),
        ]);
    }

    public function show(Conversation $conversation): Response
    {
        $alter = $this->front->currentOrFail();
        $mine = $this->messaging->correspondentFor($alter);

        $this->authorizeParticipation($conversation, $mine);

        $other = $conversation->otherParticipant($mine);
        $showsAuthor = $this->showsAuthorFor($other);

        // Ouvrir la conversation vaut lecture.
        $conversation->participants()->updateExistingPivot($mine->getKey(), ['last_read_at' => now()]);

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
        $target = Alter::query()
            ->where('handle_key', $this->handles->normalize(ltrim($data['handle'], '@')))
            ->firstOrFail();

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
