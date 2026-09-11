<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Entité adressable en messagerie : un alter, ou un système entier.
 *
 * Les quatre combinaisons (alter↔alter, alter↔système, système↔alter,
 * système↔système) empruntent le même code.
 */
class Correspondent extends Model
{
    public const TYPE_ALTER = 'alter';

    public const TYPE_SYSTEM = 'system';

    protected $fillable = ['type', 'ref_id'];

    /** @return BelongsToMany<Conversation, $this> */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')->withTimestamps();
    }

    public function isAlter(): bool
    {
        return $this->type === self::TYPE_ALTER;
    }

    /** L'entité derrière le correspondant. */
    public function subject(): Alter|System|null
    {
        return $this->isAlter()
            ? Alter::withTrashed()->find($this->ref_id)
            : System::find($this->ref_id);
    }

    /** Nom affiché : le nom de l'alter, ou le nom public du système. */
    public function displayName(): string
    {
        $subject = $this->subject();

        if ($subject instanceof Alter) {
            return $subject->trashed() ? 'Inconnu' : $subject->name;
        }

        return $subject?->display_name ?? 'Compte sans nom';
    }
}
