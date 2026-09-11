<?php

namespace App\Http\Resources;

use App\Models\Alter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializer public d'un alter. Ne contient AUCUNE donnée système :
 * c'est le point de passage obligé de l'invariant d'anti-corrélation.
 *
 * @mixin Alter
 */
class AlterResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        // Un alter supprimé ne laisse plus ni nom ni handle derrière lui.
        if ($this->trashed()) {
            return [
                'id' => $this->uuid,
                'name' => 'Inconnu',
                'handle' => null,
                'pronouns' => null,
                'bio' => null,
                'avatar_url' => null,
                'privacy_level' => null,
                'is_private' => true,
                'deleted' => true,
            ];
        }

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'handle' => $this->handle,
            'pronouns' => $this->pronouns,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'privacy_level' => $this->privacy_level->value,
            'is_private' => ! $this->privacy_level->isOpen(),
            'deleted' => false,
        ];
    }
}
