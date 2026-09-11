<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasPublicUuid;

    protected $fillable = ['content', 'author_correspondent_id', 'author_alter_id'];

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<Correspondent, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Correspondent::class, 'author_correspondent_id');
    }

    /** @return BelongsTo<Alter, $this> */
    public function authorAlter(): BelongsTo
    {
        return $this->belongsTo(Alter::class, 'author_alter_id')->withTrashed();
    }
}
