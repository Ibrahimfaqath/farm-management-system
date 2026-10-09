<?php

namespace App\Enums;

enum StockDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => __('In'),
            self::Out => __('Out'),
        };
    }

    public function sign(): int
    {
        return $this === self::In ? 1 : -1;
    }
}
