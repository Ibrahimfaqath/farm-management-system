<?php

namespace App\Enums;

enum BatchStatus: string
{
    case Active = 'active';
    case Depleted = 'depleted';
    case Expired = 'expired';
    case Quarantined = 'quarantined';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Depleted => __('Depleted'),
            self::Expired => __('Expired'),
            self::Quarantined => __('Quarantined'),
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Active;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
