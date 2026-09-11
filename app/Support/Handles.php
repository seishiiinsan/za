<?php

namespace App\Support;

use App\Models\Alter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Politique des handles.
 *
 * Le handle est l'identité publique d'un alter : il désigne une personne dans
 * une URL, sous un post, dans une invitation. Deux handles trop ressemblants,
 * ou un handle repris juste après avoir été libéré, servent à se faire passer
 * pour quelqu'un d'autre auprès de ses abonnés.
 */
class Handles
{
    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 30;

    /** Délai entre deux changements, et durée de quarantaine de l'ancien handle. */
    public const COOLDOWN_DAYS = 30;

    /**
     * Chiffres qui se confondent avec des lettres, ramenés sur la lettre.
     *
     * Seuls les chiffres sont normalisés : `l` et `i` restent deux lettres
     * distinctes, sans quoi @lila et @iiia deviendraient le même handle.
     */
    protected const CONFUSABLES = [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't',
    ];

    /** Noms de service : jamais attribués, pour éviter qu'on se fasse passer pour Za. */
    protected const RESERVED = [
        'admin', 'administrateur', 'aide', 'api', 'assistance', 'contact', 'equipe', 'help',
        'info', 'legal', 'moderation', 'moderateur', 'officiel', 'rgpd', 'root', 'securite',
        'security', 'staff', 'support', 'systeme', 'system', 'team', 'za',
    ];

    /** Forme canonique servant à l'unicité. */
    public function normalize(string $handle): string
    {
        $handle = strtolower(trim($handle));
        $handle = str_replace('_', '', $handle);

        return strtr($handle, self::CONFUSABLES);
    }

    public function isReservedWord(string $handle): bool
    {
        return in_array($this->normalize($handle), array_map(
            fn (string $word) => $this->normalize($word),
            self::RESERVED
        ), true);
    }

    public function matchesFormat(string $handle): bool
    {
        return (bool) preg_match('/^[a-z0-9_]{'.self::MIN_LENGTH.','.self::MAX_LENGTH.'}$/', $handle);
    }

    /** Le handle est-il disponible pour cet alter ? */
    public function isAvailableFor(string $handle, ?Alter $alter = null): bool
    {
        $key = $this->normalize($handle);

        $takenByAnother = Alter::withTrashed()
            ->where('handle_key', $key)
            ->when($alter, fn ($query) => $query->whereKeyNot($alter->getKey()))
            ->exists();

        return ! $takenByAnother && ! $this->isQuarantined($key, $alter);
    }

    /** Un handle libéré par un changement récent reste bloqué. */
    public function isQuarantined(string $handleKey, ?Alter $alter = null): bool
    {
        return DB::table('handle_reservations')
            ->where('handle_key', $handleKey)
            ->where('available_at', '>', now())
            ->when($alter, fn ($query) => $query->where(fn ($q) => $q
                ->whereNull('alter_id')
                ->orWhere('alter_id', '!=', $alter->getKey())))
            ->exists();
    }

    /** Date à partir de laquelle cet alter peut changer de handle. */
    public function nextChangeAllowedAt(Alter $alter): ?Carbon
    {
        return $alter->handle_changed_at?->copy()->addDays(self::COOLDOWN_DAYS);
    }

    public function canChangeHandle(Alter $alter): bool
    {
        $next = $this->nextChangeAllowedAt($alter);

        return $next === null || $next->isPast();
    }

    /** Met l'ancien handle en quarantaine après un changement. */
    public function quarantine(string $handle, Alter $alter): void
    {
        DB::table('handle_reservations')->updateOrInsert(
            ['handle_key' => $this->normalize($handle)],
            [
                'alter_id' => $alter->getKey(),
                'available_at' => now()->addDays(self::COOLDOWN_DAYS),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /** Handle interne posé sur un alter supprimé, pour libérer le sien aussitôt. */
    public function placeholderFor(Alter $alter): string
    {
        return 'supprime_'.substr(str_replace('-', '', $alter->uuid), 0, 12);
    }
}
