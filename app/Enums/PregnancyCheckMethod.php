<?php

namespace App\Enums;

enum PregnancyCheckMethod: string
{
    case Rectal = 'rectal';
    case Ultrasound = 'ultrasound';
    case BloodTest = 'blood_test';
    case Observation = 'observation';

    public function label(): string
    {
        return match ($this) {
            self::Rectal => __('Rectal palpation'),
            self::Ultrasound => __('Ultrasound'),
            self::BloodTest => __('Blood test'),
            self::Observation => __('Observation'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
