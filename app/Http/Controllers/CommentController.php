<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Support\BlockList;
use App\Support\Front;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(protected Front $front, protected BlockList $blocks) {}

    public function store(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $alter = $this->front->currentOrFail();

        abort_unless($alter->canReact(), 403, 'Ce grade de confidentialité ne permet pas de commenter.');
        abort_unless($post->isVisibleTo($alter), 404);
        abort_if($post->authors->contains(fn ($author) => $this->blocks->blocks($alter, $author)), 404);

        $comment = new Comment($data);
        $comment->alter_id = $alter->getKey();
        $post->comments()->save($comment);

        return back()->with('status', 'Commentaire publié.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        // Son auteur, ou un auteur du post commenté, peut retirer un commentaire.
        $alterIds = $request->user()->alters()->pluck('id');

        $allowed = $alterIds->contains($comment->alter_id)
            || $comment->post->authors()->whereIn('alters.id', $alterIds)->exists();

        abort_unless($allowed, 404);

        $comment->delete();

        return back()->with('status', 'Commentaire supprimé.');
    }
}
