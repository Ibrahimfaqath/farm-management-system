<?php

namespace App\Enums;

/**
 * Breeding facet of an animal, maintained from breeding, pregnancy and birth
 * events. Males use NotApplicable.
 */
enum BreedingStatus: string
{
    case Open = 'open';
    case Bred = 'bred';
    case Pregnant = 'pregnant';
    case ConfirmedPregnant = 'confirmed_pregnant';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open'),
            self::Bred => __('Bred'),
            self::Pregnant => __('Pregnant'),
            self::ConfirmedPregnant => __('Pregnant (confirmed)'),
            self::NotApplicable => __('Not applicable'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'badge-ghost',
            self::Bred => 'badge-soft badge-info',
            self::Pregnant => 'badge-soft badge-warning',
            self::ConfirmedPregnant => 'badge-soft badge-primary',
            self::NotApplicable => 'badge-ghost',
        };
    }

    public function isPregnant(): bool
    {
        return $this === self::Pregnant || $this === self::ConfirmedPregnant;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
