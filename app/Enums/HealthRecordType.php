<?php

namespace App\Enums;

enum HealthRecordType: string
{
    case Observation = 'observation';
    case Checkup = 'checkup';
    case Diagnosis = 'diagnosis';
    case FollowUp = 'follow_up';
    case Routine = 'routine';

    public function label(): string
    {
        return match ($this) {
            self::Observation => __('Observation'),
            self::Checkup => __('Checkup'),
            self::Diagnosis => __('Diagnosis'),
            self::FollowUp => __('Follow-up'),
            self::Routine => __('Routine'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
