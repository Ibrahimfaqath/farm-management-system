<?php

namespace App\Enums;

enum CashDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => __('Log in'),
            self::Out => __('Log out'),
        };
    }

    public function sign(): int
    {
        return $this === self::In ? 1 : -1;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
