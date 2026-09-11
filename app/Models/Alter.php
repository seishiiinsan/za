<?php

namespace App\Models;

use App\Enums\PrivacyLevel;
use Database\Factories\AlterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /** Alters suivis par cet alter. @return BelongsToMany<Alter, $this> */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(Alter::class, 'follows', 'follower_alter_id', 'followed_alter_id')
            ->withPivot('accepted')
            ->withTimestamps();
    }

    /** Alters qui suivent cet alter. @return BelongsToMany<Alter, $this> */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Alter::class, 'follows', 'followed_alter_id', 'follower_alter_id')
            ->withPivot('accepted')
            ->withTimestamps();
    }

    public function isFollowedBy(?Alter $alter): bool
    {
        return $alter !== null && $this->followers()
            ->wherePivot('accepted', true)
            ->whereKey($alter->getKey())
            ->exists();
    }

    public function hasPendingRequestFrom(?Alter $alter): bool
    {
        return $alter !== null && $this->followers()
            ->wherePivot('accepted', false)
            ->whereKey($alter->getKey())
            ->exists();
    }

    /** Un alter privé fait valider ses abonnés. */
    public function requiresFollowApproval(): bool
    {
        return ! $this->privacy_level->isOpen();
    }

    /** Les listes followers/abonnements sont masquées par défaut (anti-corrélation). */
    public function showsConnections(): bool
    {
        return (bool) ($this->settings['show_connections'] ?? false);
    }
}
