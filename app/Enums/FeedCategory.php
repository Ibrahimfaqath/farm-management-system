<?php

namespace App\Enums;

enum FeedCategory: string
{
    case Roughage = 'roughage';
    case Concentrate = 'concentrate';
    case Silage = 'silage';
    case Mineral = 'mineral';
    case Vitamin = 'vitamin';
    case Byproduct = 'byproduct';

    public function label(): string
    {
        return match ($this) {
            self::Roughage => __('Roughage'),
            self::Concentrate => __('Concentrate'),
            self::Silage => __('Silage'),
            self::Mineral => __('Mineral'),
            self::Vitamin => __('Vitamin'),
            self::Byproduct => __('By-product'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
