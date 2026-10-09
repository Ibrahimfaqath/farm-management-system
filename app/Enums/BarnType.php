<?php

namespace App\Enums;

enum BarnType: string
{
    case Dairy = 'dairy';
    case Calf = 'calf';
    case Dry = 'dry';
    case Quarantine = 'quarantine';
    case Breeding = 'breeding';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Dairy => __('Dairy barn'),
            self::Calf => __('Calf barn'),
            self::Dry => __('Dry barn'),
            self::Quarantine => __('Quarantine'),
            self::Breeding => __('Breeding barn'),
            self::Other => __('Other'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
