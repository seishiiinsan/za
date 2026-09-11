<?php

namespace App\Support;

use App\Models\Alter;
use App\Models\Block;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcule, pour un alter donné, la liste des alters masqués.
 *
 * Le masquage est réciproque : bloquer quelqu'un, c'est aussi disparaître de
 * sa vue. Un blocage d'alter ne porte que sur cet alter ; un blocage de système
 * porte sur tous ses alters, sans jamais nommer le système au bloqueur.
 */
class BlockList
{
    /** @var array<int, Collection<int, int>> */
    protected array $cache = [];

    /** @return Collection<int, int> */
    public function hiddenFrom(?Alter $viewer): Collection
    {
        if ($viewer === null) {
            return collect();
        }

        return $this->cache[$viewer->getKey()] ??= $this->outgoing($viewer)
            ->merge($this->incoming($viewer))
            ->unique()
            ->values();
    }

    public function blocks(Alter $viewer, Alter $target): bool
    {
        return $this->hiddenFrom($viewer)->contains($target->getKey());
    }

    /** Alters masqués parce que le lecteur les a bloqués. @return Collection<int, int> */
    protected function outgoing(Alter $viewer): Collection
    {
        $blocks = Block::query()->where('blocker_alter_id', $viewer->getKey())->get();

        $alterIds = $blocks->where('target_type', Block::TARGET_ALTER)->pluck('target_ref_id');
        $systemIds = $blocks->where('target_type', Block::TARGET_SYSTEM)->pluck('target_ref_id');

        return $alterIds->merge($this->altersOfSystems($systemIds));
    }

    /** Alters masqués parce qu'ils ont bloqué le lecteur ou son système. @return Collection<int, int> */
    protected function incoming(Alter $viewer): Collection
    {
        return Block::query()
            ->where(fn ($query) => $query
                ->where(fn ($q) => $q->where('target_type', Block::TARGET_ALTER)
                    ->where('target_ref_id', $viewer->getKey()))
                ->orWhere(fn ($q) => $q->where('target_type', Block::TARGET_SYSTEM)
                    ->where('target_ref_id', $viewer->system_id)))
            ->pluck('blocker_alter_id');
    }

    /**
     * @param  Collection<int, int>  $systemIds
     * @return Collection<int, int>
     */
    protected function altersOfSystems(Collection $systemIds): Collection
    {
        if ($systemIds->isEmpty()) {
            return collect();
        }

        return collect(DB::table('alters')->whereIn('system_id', $systemIds)->pluck('id'));
    }
}
