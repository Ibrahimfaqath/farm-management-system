<?php

namespace App\Enums;

/**
 * Stored invoice status. "Overdue" is derived from the due date and the
 * outstanding balance so it can never disagree with the numbers.
 */
enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => __('Unpaid'),
            self::Partial => __('Partially paid'),
            self::Paid => __('Paid'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unpaid => 'badge-soft badge-warning',
            self::Partial => 'badge-soft badge-info',
            self::Paid => 'badge-soft badge-success',
            self::Cancelled => 'badge-soft badge-error',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
