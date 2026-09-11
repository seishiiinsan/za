<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Front;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(protected Front $front) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $alter = $this->front->currentOrFail();

        $post = Post::create([
            'content' => $data['content'],
            'status' => Post::STATUS_PUBLISHED,
        ]);

        // MVP : post solo => exactement une ligne dans le pivot, déjà acceptée.
        $post->authors()->attach($alter->getKey(), ['accepted' => true]);

        return back()->with('status', 'Post publié.');
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

    /** Un post n'est éditable que par un de ses auteurs, et depuis le bon système. */
    protected function authorizePost(Request $request, Post $post): void
    {
        $alterIds = $request->user()->alters()->pluck('id');

        abort_unless($post->authors()->whereIn('alters.id', $alterIds)->exists(), 404);
    }
}
