<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Ajoute un identifiant public opaque à côté de la clé primaire interne.
 *
 * Les clés auto-incrémentées sont un vecteur de corrélation : deux alters créés
 * à la suite par un même système porteraient des ids voisins. Seul l'uuid sort.
 */
trait HasPublicUuid
{
    protected static function bootHasPublicUuid(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
