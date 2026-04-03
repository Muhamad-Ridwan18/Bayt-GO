<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';
    case Muthowif = 'muthowif';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Customer => 'Jamaah',
            self::Muthowif => 'Muthowif',
        };
    }
}
