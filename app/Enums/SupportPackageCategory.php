<?php

namespace App\Enums;

enum SupportPackageCategory: string
{
    case Tawaf = 'tawaf';
    case Umrah = 'umrah';
    case Ziarah = 'ziarah';
    case Mobility = 'mobility';
    case Other = 'other';

    public function label(): string
    {
        return __('enums.support_package_category.'.$this->value);
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Tawaf,
            self::Umrah,
            self::Ziarah,
            self::Mobility,
            self::Other,
        ];
    }
}
