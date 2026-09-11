<?php

namespace App\Http\Controllers;

use App\Models\Alter;
use App\Models\Post;
use App\Support\Front;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PostController extends Controller
{
    public function __construct(protected Front $front) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'co_authors' => ['array', 'max:9'],
            'co_authors.*' => ['uuid'],
        ]);

        $alter = $this->front->currentOrFail();

        abort_unless($alter->canPublish(), 403, 'Ce grade de confidentialité ne permet pas de publier.');

        $invited = $this->invitedAuthors($data['co_authors'] ?? [], $alter);

        $post = Post::create(['content' => $data['content']]);

        // L'auteur qui écrit accepte de fait ; chaque invité doit consentir.
        // Co-front (même système) et cross-post (systèmes différents) suivent
        // exactement le même chemin : aucune règle spéciale même-système.
        $post->authors()->attach($alter->getKey(), ['accepted' => true]);

        foreach ($invited as $coAuthor) {
            $post->authors()->attach($coAuthor->getKey(), ['accepted' => false]);
        }

        $post->syncStatus();

        return back()->with('status', $invited->isEmpty()
            ? 'Post publié.'
            : 'Post en attente : il sera publié quand tous les auteurs auront accepté.');
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorizePost($request, $post);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $post->update($data);

        return back()->with('status', 'Post modifié.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->authorizePost($request, $post);

        $post->delete();

        return back()->with('status', 'Post supprimé.');
    }

    /**
     * Les alters invités comme co-auteurs, hors auteur courant et hors doublons.
     *
     * @param  array<int, string>  $uuids
     * @return Collection<int, Alter>
     */
    protected function invitedAuthors(array $uuids, Alter $author)
    {
        return Alter::query()
            ->whereIn('uuid', array_unique($uuids))
            ->whereKeyNot($author->getKey())
            ->get()
            // Un alter en lecture seule ne peut pas être embarqué comme co-auteur.
            ->filter(fn (Alter $alter) => $alter->canPublish())
            ->values();
    }

    /** Un post n'est éditable que par un de ses auteurs, et depuis le bon système. */
    protected function authorizePost(Request $request, Post $post): void
    {
        $alterIds = $request->user()->alters()->pluck('id');

        abort_unless($post->authors()->whereIn('alters.id', $alterIds)->exists(), 404);
    }
}
