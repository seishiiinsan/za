<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un post n'a pas d'auteur direct : les auteurs vivent dans le pivot `post_authors`,
 * ce qui permet les co-posts / cross-posts (v2) sans refonte de schéma.
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasPublicUuid;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = ['content', 'status'];

    /** @return BelongsToMany<Alter, $this> */
    public function authors(): BelongsToMany
    {
        // `withTrashed` : un auteur supprimé reste rattaché au post, mais il
        // est rendu anonyme par le serializer public.
        return $this->belongsToMany(Alter::class, 'post_authors')
            ->withTrashed()
            ->withPivot('accepted')
            ->withTimestamps();
    }

    /** Alters ayant réagi (like). @return BelongsToMany<Alter, $this> */
    public function reactions(): BelongsToMany
    {
        return $this->belongsToMany(Alter::class, 'reactions')->withTimestamps();
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @param Builder<Post> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Compteurs de réactions et de commentaires, plus l'état du lecteur.
     *
     * @param  Builder<Post>  $query
     */
    public function scopeWithViewerContext(Builder $query, ?Alter $viewer): void
    {
        $query->withCount(['reactions', 'comments'])
            ->addSelect(['reacted' => Reaction::query()
                ->selectRaw('1')
                ->whereColumn('reactions.post_id', 'posts.id')
                ->where('reactions.alter_id', $viewer?->getKey() ?? 0)
                ->limit(1),
            ]);
    }

    /** Un post est-il lisible par cet alter ? Un seul auteur visible suffit. */
    public function isVisibleTo(?Alter $viewer): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        return $this->authors->contains(fn (Alter $author) => $author->isVisibleTo($viewer));
    }

    /** Un post est publié dès que tous ses auteurs ont accepté. */
    public function syncStatus(): void
    {
        $allAccepted = ! $this->authors()->wherePivot('accepted', false)->exists();

        $this->update(['status' => $allAccepted ? self::STATUS_PUBLISHED : self::STATUS_PENDING]);
    }
}
