<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un message expose son correspondant auteur. L'alter réel n'apparaît que si
 * le système a activé « afficher l'auteur·e » — sinon la surface système reste
 * entière et aucun alter n'est nommé.
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    public function __construct($resource, protected bool $showsAuthor = false)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'content' => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
            'author_name' => $this->author->displayName(),
            'author_alter' => $this->showsAuthor && $this->authorAlter
                ? (new AlterResource($this->authorAlter))->resolve()
                : null,
        ];
    }
}
