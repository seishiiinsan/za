<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlterNotification extends Model
{
    use HasPublicUuid;

    public const TYPE_FOLLOW = 'follow';

    public const TYPE_FOLLOW_REQUEST = 'follow_request';

    public const TYPE_POST_INVITATION = 'post_invitation';

    public const TYPE_REACTION = 'reaction';

    public const TYPE_COMMENT = 'comment';

    public const TYPE_MESSAGE = 'message';

    protected $fillable = ['alter_id', 'type', 'group_uuid', 'payload', 'read_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'read_at' => 'datetime'];
    }

    /** @return BelongsTo<Alter, $this> */
    public function alter(): BelongsTo
    {
        return $this->belongsTo(Alter::class)->withTrashed();
    }
}
