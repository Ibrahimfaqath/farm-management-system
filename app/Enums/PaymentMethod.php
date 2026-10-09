<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case DebitCard = 'debit_card';
    case CreditCard = 'credit_card';
    case EWallet = 'ewallet';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('Cash'),
            self::Transfer => __('Bank transfer'),
            self::DebitCard => __('Debit card'),
            self::CreditCard => __('Credit card'),
            self::EWallet => __('E-wallet'),
            self::Cheque => __('Cheque'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
