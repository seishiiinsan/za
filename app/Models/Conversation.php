<?php

namespace App\Models;

use App\Enums\MessagingMode;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasPublicUuid;

    protected $fillable = ['effective_mode'];

    protected function casts(): array
    {
        return ['effective_mode' => MessagingMode::class];
    }

    /** @return BelongsToMany<Correspondent, $this> */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Correspondent::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest('created_at');
    }

    public function otherParticipant(Correspondent $mine): ?Correspondent
    {
        return $this->participants->firstWhere('id', '!=', $mine->getKey());
    }
}
