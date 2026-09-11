<?php

namespace App\Enums;

/**
 * Grade de confidentialité d'un alter.
 *
 * `unlisted` ne cache pas l'existence : il retire de la recherche, pas des
 * traces publiques (une réaction reste visible). Le nom dit ce qu'il tient.
 */
enum PrivacyLevel: string
{
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';
    case ReadOnly = 'readonly';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Privé',
            self::Unlisted => 'Non-listé',
            self::ReadOnly => 'Lecture',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Public => 'Cherchable, profil ouvert.',
            self::Private => 'Cherchable, profil visible après acceptation de la demande.',
            self::Unlisted => 'Retiré de la recherche. Le profil reste accessible par son lien, et une réaction laisse une trace publique.',
            self::ReadOnly => 'Retiré de la recherche, aucun profil public, aucune publication ni réaction. Feed et messages privés uniquement.',
        };
    }

    /** Le profil est-il ouvert sans relation acceptée ? */
    public function isOpen(): bool
    {
        return $this === self::Public || $this === self::Unlisted;
    }

    /** Apparaît dans les résultats de recherche. */
    public function isSearchable(): bool
    {
        return $this === self::Public || $this === self::Private;
    }

    /** Dispose d'un profil public, ne serait-ce que par lien direct. */
    public function hasPublicProfile(): bool
    {
        return $this !== self::ReadOnly;
    }

    /** Peut publier (posts, co-écriture). */
    public function canPublish(): bool
    {
        return $this !== self::ReadOnly;
    }

    /** Peut réagir (like, commentaire). */
    public function canReact(): bool
    {
        return $this !== self::ReadOnly;
    }
}
