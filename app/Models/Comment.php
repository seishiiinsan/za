<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, HasPublicUuid;

    protected $fillable = ['content'];

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @return BelongsTo<Alter, $this> */
    public function author(): BelongsTo
    {
        // Un auteur supprimé reste rattaché : le serializer public l'anonymise.
        return $this->belongsTo(Alter::class, 'alter_id')->withTrashed();
    }
}
