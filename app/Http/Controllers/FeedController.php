<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Support\Front;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function index(Request $request, Front $front): Response
    {
        $alter = $front->currentOrFail();

        $followedIds = $alter->following()->wherePivot('accepted', true)->pluck('alters.id');

        $posts = Post::query()
            ->published()
            ->with(['authors', 'comments.author'])
            ->withViewerContext($alter)
            ->whereHas('authors', fn ($query) => $query->whereIn('alters.id', $followedIds))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Feed/Index', [
            'posts' => PostResource::collection($posts),
        ]);
    }
}
