<?php

namespace App\Enums;

enum CustomerType: string
{
    case Personal = 'personal';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::Company => 'Perusahaan',
        };
    }
}
