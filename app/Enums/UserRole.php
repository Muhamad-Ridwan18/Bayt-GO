<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';
    case Muthowif = 'muthowif';

    public function label(): string
    {
        return __('enums.user_role.'.$this->value);
    }
}
