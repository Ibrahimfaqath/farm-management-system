<?php

namespace App\Enums;

enum CashCategory: string
{
    case CustomerReceipt = 'customer_receipt';
    case SupplierPayment = 'supplier_payment';
    case Expense = 'expense';
    case OtherIncome = 'other_income';
    case OtherOutflow = 'other_outflow';
    case OwnerDraw = 'owner_draw';
    case Loan = 'loan';

    public function label(): string
    {
        return match ($this) {
            self::CustomerReceipt => __('Customer receipt'),
            self::SupplierPayment => __('Supplier payment'),
            self::Expense => __('Expense payment'),
            self::OtherIncome => __('Other income'),
            self::OtherOutflow => __('Other outflow'),
            self::OwnerDraw => __('Owner draw'),
            self::Loan => __('Loan receipt'),
        };
    }

    public function defaultDirection(): CashDirection
    {
        return in_array($this, [self::CustomerReceipt, self::OtherIncome, self::Loan], true)
            ? CashDirection::In
            : CashDirection::Out;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
