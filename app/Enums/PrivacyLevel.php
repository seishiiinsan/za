<?php

namespace App\Enums;

enum PrivacyLevel: string
{
    case Public = 'public';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Privé',
        };
    }

    /** Le profil est-il visible sans relation acceptée ? */
    public function isOpen(): bool
    {
        return $this === self::Public;
    }
}
