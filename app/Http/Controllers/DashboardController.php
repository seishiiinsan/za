<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard système : feed agrégé de tous les alters du système.
 * Strictement privé — jamais accessible depuis une route publique.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $alters = $request->user()->alters()->orderBy('name')->get();
        $alterIds = $alters->pluck('id');

        $posts = Post::query()
            ->with('authors')
            ->whereHas('authors', fn ($query) => $query->whereIn('alters.id', $alterIds))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Dashboard', [
            'alters' => AlterResource::collection($alters),
            'posts' => PostResource::collection($posts),
            'pendingRequests' => $alters->sum(
                fn ($alter) => $alter->followers()->wherePivot('accepted', false)->count()
            ),
        ]);
    }
}
