<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Http\Resources\PostResource;
use App\Models\Alter;
use App\Support\Front;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil public d'un alter. Surface la plus sensible : aucune donnée système,
 * et rien ne doit permettre de deviner les autres alters du même système.
 */
class AlterProfileController extends Controller
{
    public function show(Request $request, string $handle, Front $front): Response
    {
        $alter = Alter::query()->searchable()->where('handle', $handle)->firstOrFail();

        $viewer = $front->current();
        $visible = $alter->isVisibleTo($viewer);

        $posts = $visible
            ? $alter->posts()->wherePivot('accepted', true)
                ->where('posts.status', 'published')
                ->with('authors')
                ->latest('posts.id')
                ->paginate(20)
                ->withQueryString()
            : null;

        return Inertia::render('Profile/Show', [
            'alter' => new AlterResource($alter),
            'visible' => $visible,
            'isSelf' => $viewer?->is($alter) ?? false,
            'isFollowing' => $alter->isFollowedBy($viewer),
            'hasPendingRequest' => $alter->hasPendingRequestFrom($viewer),
            'posts' => $posts ? PostResource::collection($posts) : null,
            'connections' => $alter->showsConnections() && $visible ? [
                'followers' => AlterResource::collection(
                    $alter->followers()->wherePivot('accepted', true)->orderBy('name')->get()
                ),
                'following' => AlterResource::collection(
                    $alter->following()->wherePivot('accepted', true)->orderBy('name')->get()
                ),
            ] : null,
            'counts' => [
                'followers' => $alter->followers()->wherePivot('accepted', true)->count(),
                'following' => $alter->following()->wherePivot('accepted', true)->count(),
            ],
        ]);
    }
}
