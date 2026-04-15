<?php

namespace App\Enums;

enum CustomerType: string
{
    case Personal = 'personal';
    case Company = 'company';

    public function label(): string
    {
        return __('enums.customer_type.'.$this->value);
    }
}
