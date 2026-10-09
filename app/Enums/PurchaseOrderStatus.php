<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Approved => __('Approved'),
            self::PartiallyReceived => __('Partially received'),
            self::Received => __('Fully received'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-ghost',
            self::Approved => 'badge-soft badge-info',
            self::PartiallyReceived => 'badge-soft badge-warning',
            self::Received => 'badge-soft badge-success',
            self::Cancelled => 'badge-soft badge-error',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
