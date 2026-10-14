<?php

namespace App\Enums;

/**
 * Base unit for quantities. Feed, inventory and milk all use decimal values but
 * the unit is stored so reports stay unambiguous.
 */
enum UnitOfMeasure: string
{
    case Kilogram = 'kg';
    case Ton = 'ton';
    case Liter = 'liter';
    case Sack = 'sack';
    case Box = 'box';
    case Cup = 'cup';
    case Pouch = 'pouch';
    case Bundle = 'bundle';
    case Head = 'head';

    public function label(): string
    {
        return match ($this) {
            self::Kilogram => __('Kilogram'),
            self::Ton => __('Ton'),
            self::Liter => __('Liter'),
            self::Sack => __('Sack'),
            self::Box => __('Box'),
            self::Cup => __('Cup'),
            self::Pouch => __('Pouch'),
            self::Bundle => __('Bundle'),
            self::Head => __('Head'),
        };
    }

    /**
     * The unit as it is written beside a number in this interface.
     *
     * These read as English plurals — "38,80 bag", "5 head", "12 bundles" —
     * in an otherwise Indonesian interface. Livestock are counted in "ekor",
     * which happens to be invariant, so "1 ekor" and "2 ekor" both read
     * correctly, which "head" never did.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Kilogram => 'kg',
            self::Ton => 'ton',
            self::Liter => 'L',
            self::Sack => 'sak',
            self::Box => 'kotak',
            self::Bundle => 'ikatan',
            self::Head => 'ekor',
            self::Cup => 'cup',
            self::Pouch => 'kemasan',
        };
    }

    /** Units that may be converted to kilograms when estimating feed value. */
    public function toKilogramsFactor(): ?float
    {
        return match ($this) {
            self::Kilogram => 1.0,
            self::Ton => 1000.0,
            self::Sack => 50.0,
            self::Head => 50.0,
            self::Box, self::Bundle, self::Liter, self::Cup, self::Pouch => null,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
