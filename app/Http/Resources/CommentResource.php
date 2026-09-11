<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Comment */
class CommentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'content' => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => new AlterResource($this->whenLoaded('author')),
        ];
    }
}
