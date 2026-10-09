<?php

namespace App\Enums;

/**
 * Type of stock movement. Direction is derived so a movement always has an
 * unambiguous effect on inventory.
 */
enum MovementType: string
{
    case Receipt = 'receipt';
    case Opening = 'opening';
    case Consumption = 'consumption';
    case Wastage = 'wastage';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case ReturnOut = 'return_out';
    case ReturnIn = 'return_in';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => __('Goods receipts'),
            self::Opening => __('Opening balance'),
            self::Consumption => __('Consumption'),
            self::Wastage => __('Wastage / Expired'),
            self::AdjustmentIn => __('Adjustment (+)'),
            self::AdjustmentOut => __('Adjustment (-)'),
            self::TransferOut => __('Transfer out'),
            self::TransferIn => __('Transfer in'),
            self::ReturnOut => __('Return to supplier'),
            self::ReturnIn => __('Return from customer'),
        };
    }

    public function direction(): StockDirection
    {
        return match ($this) {
            self::Receipt, self::Opening, self::AdjustmentIn, self::TransferIn, self::ReturnIn => StockDirection::In,
            self::Consumption, self::Wastage, self::AdjustmentOut, self::TransferOut, self::ReturnOut => StockDirection::Out,
        };
    }

    public function requiresReason(): bool
    {
        return in_array($this, [
            self::Wastage,
            self::AdjustmentIn,
            self::AdjustmentOut,
            self::TransferOut,
            self::TransferIn,
            self::ReturnOut,
            self::ReturnIn,
            self::Opening,
        ], true);
    }

    public function badgeClass(): string
    {
        return match ($this->direction()) {
            StockDirection::In => 'badge-soft badge-success',
            StockDirection::Out => 'badge-soft badge-warning',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
