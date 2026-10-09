<?php

namespace App\Enums;

enum HealthRecordStatus: string
{
    case Open = 'open';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open'),
            self::Monitoring => __('Monitoring'),
            self::Resolved => __('Resolved'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'badge-soft badge-error',
            self::Monitoring => 'badge-soft badge-warning',
            self::Resolved => 'badge-soft badge-success',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Resolved;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
