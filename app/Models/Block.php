<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blocage émis par un alter, visant un alter ou un système entier.
 *
 * Bloquer un alter ne masque que cet alter : les autres alters du même système
 * continuent de voir le contenu, puisque ce sont des personnes distinctes.
 * Bloquer le système est le seul vecteur qui ferme le retour par un autre alter.
 */
class Block extends Model
{
    public const TARGET_ALTER = 'alter';

    public const TARGET_SYSTEM = 'system';

    protected $fillable = ['blocker_alter_id', 'target_type', 'target_ref_id'];

    /** @return BelongsTo<Alter, $this> */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(Alter::class, 'blocker_alter_id');
    }
}
