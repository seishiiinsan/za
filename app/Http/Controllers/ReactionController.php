<?php

namespace App\Http\Controllers;

use App\Models\AlterNotification;
use App\Models\Post;
use App\Support\BlockList;
use App\Support\Front;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;

/**
 * Likes. L'auteur d'une réaction est toujours un alter : une réaction laisse
 * une trace publique, y compris pour un alter non-listé (§7).
 */
class ReactionController extends Controller
{
    public function __construct(
        protected Front $front,
        protected BlockList $blocks,
        protected Notifier $notifier,
    ) {}

    public function store(Post $post): RedirectResponse
    {
        $alter = $this->front->currentOrFail();

        abort_unless($alter->canReact(), 403, 'Ce grade de confidentialité ne permet pas de réagir.');
        abort_unless($post->isVisibleTo($alter), 404);
        abort_if($post->authors->contains(fn ($author) => $this->blocks->blocks($alter, $author)), 404);

        $post->reactions()->syncWithoutDetaching([$alter->getKey()]);

        $this->notifier->notifyAuthors($post->authors, $alter, AlterNotification::TYPE_REACTION, [
            'actor' => $alter->name,
            'handle' => $alter->handle,
            'post' => $post->uuid,
        ]);

        return back()->with('status', 'Réaction ajoutée.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $alter = $this->front->currentOrFail();

        $post->reactions()->detach($alter->getKey());

        return back()->with('status', 'Réaction retirée.');
    }
}
