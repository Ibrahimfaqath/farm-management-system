<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Feed = 'feed';
    case Veterinary = 'veterinary';
    case Labor = 'labor';
    case Utilities = 'utilities';
    case Maintenance = 'maintenance';
    case Transportation = 'transportation';
    case Equipment = 'equipment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Feed => __('Feed'),
            self::Veterinary => __('Veterinary'),
            self::Labor => __('Labor'),
            self::Utilities => __('Utilities'),
            self::Maintenance => __('Maintenance'),
            self::Transportation => __('Transportation'),
            self::Equipment => __('Equipment'),
            self::Other => __('Other'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
