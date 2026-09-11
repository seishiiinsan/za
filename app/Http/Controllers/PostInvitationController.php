<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Support\Front;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invitations de co-écriture.
 *
 * Un post à plusieurs auteurs reste `pending` tant qu'un invité n'a pas
 * accepté : personne n'est publié sans son consentement.
 */
class PostInvitationController extends Controller
{
    public function __construct(protected Front $front) {}

    public function index(): Response
    {
        $alter = $this->front->currentOrFail();

        $posts = Post::query()
            ->with('authors')
            ->whereHas('authors', fn ($query) => $query
                ->whereKey($alter->getKey())
                ->where('post_authors.accepted', false))
            ->latest('id')
            ->get();

        return Inertia::render('Posts/Invitations', [
            'invitations' => PostResource::collection($posts),
        ]);
    }

    public function accept(Post $post): RedirectResponse
    {
        $alter = $this->front->currentOrFail();

        $this->pendingInvitation($post, $alter->getKey())->update([
            'accepted' => true,
            'updated_at' => now(),
        ]);

        $post->syncStatus();

        return back()->with('status', 'Co-écriture acceptée.');
    }

    public function decline(Post $post): RedirectResponse
    {
        $alter = $this->front->currentOrFail();

        $this->pendingInvitation($post, $alter->getKey());

        // Refuser retire l'alter du post : les auteurs restants peuvent publier.
        $post->authors()->detach($alter->getKey());
        $post->syncStatus();

        return back()->with('status', 'Co-écriture refusée.');
    }

    /** @return Builder */
    protected function pendingInvitation(Post $post, int $alterId)
    {
        $invitation = DB::table('post_authors')
            ->where('post_id', $post->getKey())
            ->where('alter_id', $alterId)
            ->where('accepted', false);

        abort_unless($invitation->exists(), 404);

        return $invitation;
    }
}
