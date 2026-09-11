<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Models\Alter;
use App\Models\Block;
use App\Support\BlockList;
use App\Support\Front;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BlockController extends Controller
{
    public function __construct(protected Front $front, protected BlockList $blocks) {}

    public function index(): Response
    {
        $alter = $this->front->currentOrFail();

        $blocks = Block::query()->where('blocker_alter_id', $alter->getKey())->get();
        $alters = Alter::query()->whereIn('id', $blocks->where('target_type', Block::TARGET_ALTER)->pluck('target_ref_id'))->get();

        return Inertia::render('Blocks/Index', [
            'alters' => AlterResource::collection($alters),
            // Les systèmes bloqués ne sont pas nommés : le bloqueur ne doit pas
            // découvrir qui ils sont, ni combien d'alters ils portent.
            'systemBlocks' => $blocks->where('target_type', Block::TARGET_SYSTEM)
                ->map(fn (Block $block) => [
                    'id' => $block->id,
                    'blocked_at' => $block->created_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    public function store(Request $request, Alter $alter): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', Rule::in([Block::TARGET_ALTER, Block::TARGET_SYSTEM])],
        ]);

        $blocker = $this->front->currentOrFail();

        abort_if($blocker->is($alter), 422, 'Un alter ne peut pas se bloquer lui-même.');
        abort_if($alter->system_id === $blocker->system_id, 422, 'Blocage impossible au sein du même système.');

        Block::firstOrCreate([
            'blocker_alter_id' => $blocker->getKey(),
            'target_type' => $data['target'],
            // Pour un blocage système, la référence est résolue côté serveur :
            // l'identifiant du système ne transite jamais par le client.
            'target_ref_id' => $data['target'] === Block::TARGET_SYSTEM ? $alter->system_id : $alter->getKey(),
        ]);

        // Un blocage coupe la relation dans les deux sens.
        $alter->followers()->detach($blocker->getKey());
        $alter->following()->detach($blocker->getKey());

        return back()->with('status', $data['target'] === Block::TARGET_SYSTEM
            ? 'Compte bloqué. Tous ses profils sont masqués, sans vous dire lesquels.'
            : 'Alter bloqué.');
    }

    public function destroy(Request $request, Block $block): RedirectResponse
    {
        $alterIds = $request->user()->alters()->pluck('id');

        abort_unless($alterIds->contains($block->blocker_alter_id), 404);

        $block->delete();

        return back()->with('status', 'Blocage retiré.');
    }
}
