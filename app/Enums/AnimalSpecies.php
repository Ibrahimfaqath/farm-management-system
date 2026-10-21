<?php

namespace App\Enums;

/**
 * The farm keeps dairy cattle only.
 *
 * Older data may still contain the string values `buffalo` or `goat`; they are
 * deliberately not enum cases anymore so no new record or filter can select
 * them. The database column stays a string for that historical compatibility.
 */
enum AnimalSpecies: string
{
    case Cattle = 'cattle';

    public function label(): string
    {
        return match ($this) {
            self::Cattle => __('Cattle'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
