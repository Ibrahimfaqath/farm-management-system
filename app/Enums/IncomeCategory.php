<?php

namespace App\Enums;

enum IncomeCategory: string
{
    case Manure = 'manure';
    case LivestockSale = 'livestock_sale';
    case OtherSale = 'other_sale';
    case Subsidy = 'subsidy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manure => __('Manure sale'),
            self::LivestockSale => __('Livestock sale'),
            self::OtherSale => __('Other sale'),
            self::Subsidy => __('Subsidy / Assistance'),
            self::Other => __('Other'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
