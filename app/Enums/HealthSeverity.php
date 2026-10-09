<?php

namespace App\Enums;

enum HealthSeverity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Serious = 'serious';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Mild => __('Mild'),
            self::Moderate => __('Moderate'),
            self::Serious => __('Serious'),
            self::Critical => __('Critical'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Mild => 'badge-ghost',
            self::Moderate => 'badge-soft badge-info',
            self::Serious => 'badge-soft badge-warning',
            self::Critical => 'badge-soft badge-error',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
