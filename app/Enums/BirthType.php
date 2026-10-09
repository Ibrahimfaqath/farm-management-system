<?php

namespace App\Enums;

enum BirthType: string
{
    case Normal = 'normal';
    case Assisted = 'assisted';
    case Difficult = 'difficult';
    case Caesarean = 'caesarean';
    case Stillborn = 'stillborn';

    public function label(): string
    {
        return match ($this) {
            self::Normal => __('Normal'),
            self::Assisted => __('Assisted'),
            self::Difficult => __('Difficult'),
            self::Caesarean => __('Caesarean'),
            self::Stillborn => __('Stillborn'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Normal => 'badge-soft badge-success',
            self::Assisted => 'badge-soft badge-info',
            self::Difficult, self::Caesarean => 'badge-soft badge-warning',
            self::Stillborn => 'badge-soft badge-error',
        };
    }

    public function producedLiveCalf(): bool
    {
        return $this !== self::Stillborn;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
