<?php

namespace App\Enums;

/**
 * Existence status of an animal. Historical animals are never deleted.
 */
enum AnimalStatus: string
{
    case Active = 'active';
    case Sold = 'sold';
    case Deceased = 'deceased';
    case Transferred = 'transferred';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Sold => __('Sold'),
            self::Deceased => __('Deceased'),
            self::Transferred => __('Transferred'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge-soft badge-success',
            self::Sold => 'badge-soft badge-info',
            self::Deceased => 'badge-soft badge-error',
            self::Transferred => 'badge-soft badge-warning',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Active;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
