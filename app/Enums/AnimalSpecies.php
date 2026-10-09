<?php

namespace App\Enums;

enum AnimalSpecies: string
{
    case Cattle = 'cattle';
    case Buffalo = 'buffalo';
    case Goat = 'goat';

    public function label(): string
    {
        return match ($this) {
            self::Cattle => __('Cattle'),
            self::Buffalo => __('Buffalo'),
            self::Goat => __('Goat'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
