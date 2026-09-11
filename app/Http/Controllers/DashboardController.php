<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Http\Resources\PostResource;
use App\Models\Alter;
use App\Models\Post;
use App\Support\Front;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Chez toi » : le profil de l'alter au front, vu de l'intérieur, et l'onglet
 * système qui agrège tous les alters. Strictement privé — aucune route
 * publique n'expose cette agrégation.
 */
class DashboardController extends Controller
{
    public function __construct(protected Front $front, protected Notifier $notifier) {}

    public function index(Request $request): Response
    {
        $alters = $request->user()->alters()->orderBy('name')->get();
        $alterIds = $alters->pluck('id');
        $active = $this->front->ensure();

        return Inertia::render('Dashboard', [
            'alter' => $active ? (new AlterResource($active))->resolve() : null,
            'counts' => $this->counts($active),
            // Onglet perso : les posts de l'alter au front.
            'posts' => $active
                ? PostResource::collection(
                    $active->posts()->wherePivot('accepted', true)
                        ->with(['authors', 'comments.author'])
                        ->withViewerContext($active)
                        ->latest('posts.id')
                        ->paginate(12)
                        ->withQueryString()
                )
                : null,
            // Onglet système : tout ce qu'écrivent les alters du système.
            'systemPosts' => PostResource::collection(
                Post::query()
                    ->with(['authors', 'comments.author'])
                    ->withViewerContext($active)
                    ->whereHas('authors', fn ($query) => $query->whereIn('alters.id', $alterIds))
                    ->latest('id')
                    ->limit(20)
                    ->get()
            ),
            'alters' => AlterResource::collection($alters),
            'followers' => $active
                ? AlterResource::collection($active->followers()->wherePivot('accepted', true)->orderBy('name')->get())
                : null,
            'following' => $active
                ? AlterResource::collection($active->following()->wherePivot('accepted', true)->orderBy('name')->get())
                : null,
            'pendingRequests' => $alters->sum(
                fn (Alter $alter) => $alter->followers()->wherePivot('accepted', false)->count()
            ),
            'pendingInvitations' => DB::table('post_authors')
                ->whereIn('alter_id', $alterIds)
                ->where('accepted', false)
                ->count(),
            // Remontée système : seuls les alters qui l'ont autorisée apparaissent.
            'notifications' => NotificationController::present(
                $this->notifier->escalatedTo($alters)->limit(20)->get()
            ),
        ]);
    }

    /** @return array<string, int> */
    protected function counts(?Alter $alter): array
    {
        if ($alter === null) {
            return ['followers' => 0, 'following' => 0, 'posts' => 0];
        }

        return [
            'followers' => $alter->followers()->wherePivot('accepted', true)->count(),
            'following' => $alter->following()->wherePivot('accepted', true)->count(),
            'posts' => $alter->posts()->wherePivot('accepted', true)->where('posts.status', 'published')->count(),
        ];
    }
}
