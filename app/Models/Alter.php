<?php

namespace App\Models;

use App\Enums\PrivacyLevel;
use Database\Factories\AlterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * L'alter est l'unité publique : profil, posts, follows.
 * `system_id` ne doit jamais sortir vers une surface publique.
 */
class Alter extends Model
{
    /** @use HasFactory<AlterFactory> */
    use HasFactory;

    protected $fillable = ['name', 'handle', 'pronouns', 'avatar_path', 'bio', 'privacy_level', 'settings'];

    /** Filet de sécurité : jamais sérialisé, même par accident. */
    protected $hidden = ['system_id'];

    protected function casts(): array
    {
        return [
            'privacy_level' => PrivacyLevel::class,
            'settings' => 'array',
        ];
    }

    /** @return BelongsTo<System, $this> */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /** Les listes followers/abonnements sont masquées par défaut (anti-corrélation). */
    public function showsConnections(): bool
    {
        return (bool) ($this->settings['show_connections'] ?? false);
    }
}
