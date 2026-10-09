<?php

namespace App\Enums;

enum AcquisitionType: string
{
    case Birth = 'birth';
    case Purchase = 'purchase';
    case Gift = 'gift';
    case Transfer = 'transfer';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Birth => __('Born on farm'),
            self::Purchase => __('Purchase'),
            self::Gift => __('Gift / Donation'),
            self::Transfer => __('Transfer in'),
            self::Unknown => __('Unknown'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
