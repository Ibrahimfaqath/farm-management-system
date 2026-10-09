<?php

namespace App\Enums;

enum MilkSession: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Morning => __('Morning'),
            self::Afternoon => __('Afternoon'),
            self::Evening => __('Evening'),
            self::Mixed => __('Combined'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
