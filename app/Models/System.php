<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\SystemFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Le système est le compte : porte d'authentification et centre de contrôle.
 * Il n'est jamais exposé sur une surface publique.
 */
class System extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<SystemFactory> */
    use HasFactory, HasPublicUuid, Notifiable;

    protected $fillable = ['email', 'password', 'display_name', 'description', 'settings'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'settings' => 'array',
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            // Chiffrées en base : elles ne servent à aucune jointure.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** Consomme un code de secours. Un code ne sert qu'une fois. */
    public function consumeRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $match = null;

        foreach ($codes as $candidate) {
            if (hash_equals($candidate, trim($code))) {
                $match = $candidate;
            }
        }

        if ($match === null) {
            return false;
        }

        $this->forceFill([
            'two_factor_recovery_codes' => array_values(array_diff($codes, [$match])),
        ])->save();

        return true;
    }

    /** @return HasMany<Alter, $this> */
    public function alters(): HasMany
    {
        return $this->hasMany(Alter::class);
    }
}
