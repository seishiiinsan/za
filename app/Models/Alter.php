<?php

namespace App\Models;

use App\Enums\PrivacyLevel;
use App\Models\Concerns\HasPublicUuid;
use App\Support\Handles;
use Database\Factories\AlterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * L'alter est l'unité publique : profil, posts, follows.
 * `system_id` ne doit jamais sortir vers une surface publique.
 */
class Alter extends Model
{
    /** @use HasFactory<AlterFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes;

    protected $fillable = ['name', 'handle', 'pronouns', 'avatar_path', 'bio', 'privacy_level', 'settings'];

    /** Filet de sécurité : jamais sérialisé, même par accident. */
    protected $hidden = ['system_id'];

    protected function casts(): array
    {
        return [
            'privacy_level' => PrivacyLevel::class,
            'settings' => 'array',
            'handle_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // La forme canonique suit toujours le handle : c'est elle qui porte
        // l'unicité en base.
        static::saving(function (Alter $alter) {
            if ($alter->isDirty('handle')) {
                $alter->handle_key = app(Handles::class)->normalize($alter->handle);
            }
        });
    }

    /** @return BelongsTo<System, $this> */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /** @return BelongsToMany<Post, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_authors')
            ->withPivot('accepted')
            ->withTimestamps();
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

    /** Le profil (posts compris) est-il lisible par $viewer ? */
    public function isVisibleTo(?Alter $viewer): bool
    {
        if ($viewer !== null && $viewer->is($this)) {
            return true;
        }

        return $this->privacy_level->isOpen() || $this->isFollowedBy($viewer);
    }

    /** Un alter privé fait valider ses abonnés. */
    public function requiresFollowApproval(): bool
    {
        return $this->privacy_level === PrivacyLevel::Private;
    }

    public function canPublish(): bool
    {
        return $this->privacy_level->canPublish();
    }

    public function canReact(): bool
    {
        return $this->privacy_level->canReact();
    }

    public function hasPublicProfile(): bool
    {
        return $this->privacy_level->hasPublicProfile();
    }

    /** @return HasMany<AlterNotification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(AlterNotification::class)->latest('id');
    }

    /** Les notifications de cet alter remontent-elles au dashboard système ? */
    public function notifiesSystem(): bool
    {
        return (bool) ($this->settings['notify_system'] ?? false);
    }

    /** Alter délégué pour traiter les notifications de celui-ci, s'il y en a un. */
    public function delegate(): ?Alter
    {
        $uuid = $this->settings['delegate_to'] ?? null;

        return $uuid === null
            ? null
            : static::query()->where('system_id', $this->system_id)->where('uuid', $uuid)->first();
    }

    /** Les listes followers/abonnements sont masquées par défaut (anti-corrélation). */
    public function showsConnections(): bool
    {
        return (bool) ($this->settings['show_connections'] ?? false);
    }

    /** Alters visibles dans la recherche. @param Builder<Alter> $query */
    public function scopeSearchable(Builder $query): void
    {
        $query->whereIn('privacy_level', array_map(
            fn (PrivacyLevel $level) => $level->value,
            array_filter(PrivacyLevel::cases(), fn (PrivacyLevel $level) => $level->isSearchable())
        ));
    }

    /** Alters disposant d'un profil public, ne serait-ce que par lien direct. @param Builder<Alter> $query */
    public function scopeWithPublicProfile(Builder $query): void
    {
        $query->whereIn('privacy_level', array_map(
            fn (PrivacyLevel $level) => $level->value,
            array_filter(PrivacyLevel::cases(), fn (PrivacyLevel $level) => $level->hasPublicProfile())
        ));
    }
}
