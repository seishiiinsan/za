<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Post */
class PostResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'content' => $this->content,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'authors' => AlterResource::collection($this->whenLoaded('authors')),
        ];
    }
}
