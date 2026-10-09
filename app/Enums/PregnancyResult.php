<?php

namespace App\Enums;

enum PregnancyResult: string
{
    case Pregnant = 'pregnant';
    case ConfirmedPregnant = 'confirmed_pregnant';
    case NotPregnant = 'not_pregnant';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Pregnant => __('Pregnant'),
            self::ConfirmedPregnant => __('Pregnant (confirmed)'),
            self::NotPregnant => __('Not pregnant'),
            self::Unknown => __('Undetermined'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pregnant => 'badge-soft badge-warning',
            self::ConfirmedPregnant => 'badge-soft badge-primary',
            self::NotPregnant => 'badge-ghost',
            self::Unknown => 'badge-ghost',
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
