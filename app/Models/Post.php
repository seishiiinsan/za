<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /** @param Builder<Post> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    /** Un post est publié dès que tous ses auteurs ont accepté. */
    public function syncStatus(): void
    {
        $allAccepted = ! $this->authors()->wherePivot('accepted', false)->exists();

        $this->update(['status' => $allAccepted ? self::STATUS_PUBLISHED : self::STATUS_PENDING]);
    }
}
