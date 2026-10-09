<?php

namespace App\Enums;

/**
 * Production facet of an animal. Kept separate from existence status so an
 * animal can be lactating, pregnant and under treatment simultaneously.
 */
enum ProductionStatus: string
{
    case Calf = 'calf';
    case Heifer = 'heifer';
    case Dry = 'dry';
    case Lactating = 'lactating';
    case Fattening = 'fattening';
    case Bull = 'bull';

    public function label(): string
    {
        return match ($this) {
            self::Calf => __('Calf'),
            self::Heifer => __('Heifer'),
            self::Dry => __('Dry'),
            self::Lactating => __('Lactating'),
            self::Fattening => __('Fattening'),
            self::Bull => __('Bull'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Lactating => 'badge-soft badge-primary',
            self::Dry => 'badge-soft badge-secondary',
            self::Heifer => 'badge-soft badge-info',
            self::Calf => 'badge-ghost',
            self::Fattening => 'badge-soft badge-warning',
            self::Bull => 'badge-ghost',
        };
    }

    public function producesMilk(): bool
    {
        return $this === self::Lactating;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
