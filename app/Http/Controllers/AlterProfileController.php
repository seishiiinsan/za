<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Http\Resources\PostResource;
use App\Models\Alter;
use App\Support\BlockList;
use App\Support\Front;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profil public d'un alter. Surface la plus sensible : aucune donnée système,
 * et rien ne doit permettre de deviner les autres alters du même système.
 */
class AlterProfileController extends Controller
{
    public function show(Request $request, string $handle, Front $front, BlockList $blocks): Response
    {
        // Le non-listé garde son profil : c'est la recherche qui l'ignore.
        $alter = Alter::query()->withPublicProfile()->where('handle', $handle)->firstOrFail();

        $viewer = $front->current();

        abort_if($viewer !== null && $blocks->blocks($viewer, $alter), 404);

        $visible = $alter->isVisibleTo($viewer);

        $posts = $visible
            ? $alter->posts()->wherePivot('accepted', true)
                ->where('posts.status', 'published')
                ->with(['authors', 'comments.author'])
                ->withViewerContext($viewer)
                ->latest('posts.id')
                ->paginate(20)
                ->withQueryString()
            : null;

        return Inertia::render('Profile/Show', [
            // `resolve()` : une ressource unique serait sinon enveloppée dans
            // une clé `data`, que la page n'attend pas.
            'alter' => (new AlterResource($alter))->resolve(),
            'visible' => $visible,
            'isSelf' => $viewer?->is($alter) ?? false,
            'isFollowing' => $alter->isFollowedBy($viewer),
            'hasPendingRequest' => $alter->hasPendingRequestFrom($viewer),
            'canBlock' => $viewer !== null && $viewer->system_id !== $alter->system_id,
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

    /** Résolution d'un handle pour inviter un co-auteur. Mêmes règles que le profil public. */
    public function lookup(string $handle): JsonResponse
    {
        $alter = Alter::query()->searchable()->where('handle', $handle)->firstOrFail();

        return response()->json((new AlterResource($alter))->resolve());
    }
}
