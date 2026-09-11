<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Models\Alter;
use App\Support\BlockList;
use App\Support\Front;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Follow strictement alter <-> alter. Un alter privé valide ses abonnés.
 */
class FollowController extends Controller
{
    public function __construct(protected Front $front, protected BlockList $blocks) {}

    public function store(Alter $alter): RedirectResponse
    {
        $follower = $this->front->currentOrFail();

        abort_if($follower->is($alter), 422, 'Un alter ne peut pas se suivre lui-même.');
        abort_if($this->blocks->blocks($follower, $alter), 404);

        $accepted = ! $alter->requiresFollowApproval();

        $alter->followers()->syncWithoutDetaching([
            $follower->getKey() => ['accepted' => $accepted],
        ]);

        return back()->with('status', $accepted ? 'Abonnement créé.' : 'Demande envoyée.');
    }

    public function destroy(Alter $alter): RedirectResponse
    {
        $follower = $this->front->currentOrFail();

        $alter->followers()->detach($follower->getKey());

        return back()->with('status', 'Abonnement retiré.');
    }

    /** Demandes en attente pour le front actif. */
    public function requests(): Response
    {
        $alter = $this->front->currentOrFail();

        return Inertia::render('Follows/Requests', [
            'requests' => AlterResource::collection(
                $alter->followers()->wherePivot('accepted', false)->orderBy('name')->get()
            ),
        ]);
    }

    public function approve(Alter $alter): RedirectResponse
    {
        $target = $this->front->currentOrFail();

        DB::table('follows')
            ->where('follower_alter_id', $alter->getKey())
            ->where('followed_alter_id', $target->getKey())
            ->update(['accepted' => true, 'updated_at' => now()]);

        return back()->with('status', 'Demande acceptée.');
    }

    public function reject(Alter $alter): RedirectResponse
    {
        $target = $this->front->currentOrFail();

        $target->followers()->wherePivot('accepted', false)->detach($alter->getKey());

        return back()->with('status', 'Demande refusée.');
    }

    /** Listes du front actif (privées, côté propriétaire). */
    public function connections(): Response
    {
        $alter = $this->front->currentOrFail();

        return Inertia::render('Follows/Index', [
            'followers' => AlterResource::collection(
                $alter->followers()->wherePivot('accepted', true)->orderBy('name')->get()
            ),
            'following' => AlterResource::collection(
                $alter->following()->wherePivot('accepted', true)->orderBy('name')->get()
            ),
        ]);
    }
}
