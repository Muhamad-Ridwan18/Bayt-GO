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
     * Hub/UI category (tawaf packages live under Kursi Roda / mobility).
     */
    public function hubCategory(): self
    {
        return match ($this) {
            self::Tawaf => self::Mobility,
            default => $this,
        };
    }

    /**
     * Categories selectable in the 5-item hub (excludes legacy tawaf).
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Mobility,
            self::Umrah,
            self::Other,
            self::Ziarah,
        ];
    }

    /**
     * DB values that belong to a hub category filter.
     *
     * @return list<string>
     */
    public function storageValues(): array
    {
        return match ($this) {
            self::Mobility => [self::Mobility->value, self::Tawaf->value],
            default => [$this->value],
        };
    }
}
