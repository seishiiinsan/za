<?php

namespace App\Enums;

/** Mode de messagerie choisi par un système, puis négocié par conversation. */
enum MessagingMode: string
{
    case Personal = 'personal';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Perso : un correspondant par alter',
            self::Shared => 'Partagée : un seul correspondant pour le système',
        };
    }
}
