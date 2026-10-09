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
    case Bag = 'bag';
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
            self::Bag => __('Sack'),
            self::Sack => __('Sack'),
            self::Box => __('Box'),
            self::Cup => __('Cup'),
            self::Pouch => __('Pouch'),
            self::Bundle => __('Bundle'),
            self::Head => __('Head'),
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Kilogram => 'kg',
            self::Ton => 'ton',
            self::Liter => 'L',
            self::Bag => 'bag',
            self::Sack => 'sack',
            self::Box => 'box',
            self::Bundle => 'bundles',
            self::Head => 'head',
            self::Cup => 'cup',
            self::Pouch => 'pouch',
        };
    }

    /** Units that may be converted to kilograms when estimating feed value. */
    public function toKilogramsFactor(): ?float
    {
        return match ($this) {
            self::Kilogram => 1.0,
            self::Ton => 1000.0,
            self::Bag => 50.0,
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
