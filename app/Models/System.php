<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\SystemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Le système est le compte : porte d'authentification et centre de contrôle.
 * Il n'est jamais exposé sur une surface publique.
 */
class System extends Authenticatable
{
    /** @use HasFactory<SystemFactory> */
    use HasFactory, HasPublicUuid, Notifiable;

    protected $fillable = ['email', 'password', 'display_name', 'description', 'settings'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    /** @return HasMany<Alter, $this> */
    public function alters(): HasMany
    {
        return $this->hasMany(Alter::class);
    }
}
