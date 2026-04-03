<?php

namespace App\Enums;

enum MuthowifServiceType: string
{
    case Group = 'group';
    case PrivateJamaah = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Group => 'Jemaah Group',
            self::PrivateJamaah => 'Jemaah Private',
        };
    }
}
