<?php

namespace App\Enums;

enum BreedingRecordType: string
{
    case HeatObserved = 'heat_observed';
    case Insemination = 'insemination';
    case NaturalMating = 'natural_mating';
    case EstrusSynchronization = 'estrus_synchronization';

    public function label(): string
    {
        return match ($this) {
            self::HeatObserved => __('Heat signs'),
            self::Insemination => __('Insemination'),
            self::NaturalMating => __('Natural mating'),
            self::EstrusSynchronization => __('Estrus synchronization'),
        };
    }

    /** Only actual mating attempts change the breeding status. */
    public function isMating(): bool
    {
        return in_array($this, [self::Insemination, self::NaturalMating], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
