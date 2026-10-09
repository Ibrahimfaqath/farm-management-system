<?php

namespace App\Services;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use App\Enums\InvoiceStatus;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\GoodsReceipt;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cash and receivable/payable movements.
 *
 * Recognising revenue is deliberately separate from receiving money: an invoice
 * raises accounts receivable, and only a payment raises cash.
 */
class FinanceService
{
    public function __construct(private readonly AuditLogger $audit) {}

    // ------------------------------------------------------------------
    // Receivables
    // ------------------------------------------------------------------

    /**
     * @param  array{
     *     customer_id: int,
     *     invoice_date: string,
     *     due_date?: string|null,
     *     payment_terms_days?: int,
     *     subtotal: float,
     *     discount_amount?: float,
     *     tax_amount?: float,
     *     sales_order_id?: int|null,
     *     notes?: string|null
     * }  $data
     * @return array{invoice: Invoice, receivable_added: float, cash_effect: float}
     */
    public function createInvoice(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user): array {
            $subtotal = round((float) $data['subtotal'], 2);
            $discount = round((float) ($data['discount_amount'] ?? 0), 2);
            $tax = round((float) ($data['tax_amount'] ?? 0), 2);
            $total = max(0, round($subtotal + $tax - $discount, 2));

            $terms = (int) ($data['payment_terms_days'] ?? 0);
            $invoiceDate = $data['invoice_date'];
            $dueDate = $data['due_date'] ?? now()->parse($invoiceDate)->addDays($terms)->toDateString();

            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'invoice_date' => $invoiceDate,
                'payment_terms_days' => $terms,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total' => $total,
                'paid_amount' => 0,
                'status' => InvoiceStatus::Unpaid,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->getKey(),
            ]);

            $this->audit->log(
                'sales.invoiced',
                $invoice,
                "Faktur {$invoice->invoice_number} dibuat untuk ".Customer::find($invoice->customer_id)?->name,
                $invoice->invoice_number,
                ['total' => $total],
            );

            return [
                'invoice' => $invoice,
                'receivable_added' => $total,
                'cash_effect' => 0.0,
            ];
        });
    }

    /**
     * Record a customer payment: reduces receivable and increases cash.
     *
     * @param  array{invoice_id?: int|null, customer_id: int, amount: float, payment_date: string, method: string, reference_number?: string|null, notes?: string|null}  $data
     * @return array{payment: Payment, invoice: ?Invoice, ar_before: float, ar_after: float, cash_in: float, status: string}
     */
    public function recordPayment(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user): array {
            $invoice = isset($data['invoice_id']) && $data['invoice_id']
                ? Invoice::query()->with('customer')->lockForUpdate()->find($data['invoice_id'])
                : null;

            if ($invoice && (int) $invoice->customer_id !== (int) $data['customer_id']) {
                throw new \InvalidArgumentException('Faktur tidak sesuai dengan pelanggan yang dipilih.');
            }

            $arBefore = $invoice?->balance() ?? 0.0;

            $payment = Payment::create([
                'payment_number' => $this->nextNumber('PAY', 'payments'),
                'invoice_id' => $invoice?->getKey(),
                'customer_id' => $data['customer_id'],
                'payment_date' => $data['payment_date'],
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user->getKey(),
            ]);

            CashTransaction::create([
                'transaction_number' => $this->nextNumber('TRX', 'cash_transactions'),
                'transaction_date' => $data['payment_date'],
                'direction' => CashDirection::In,
                'category' => CashCategory::CustomerReceipt,
                'amount' => $payment->amount,
                'description' => 'Pembayaran pelanggan'.($invoice ? ' '.$invoice->invoice_number : ''),
                'method' => $data['method'],
                'reference_number' => $data['reference_number'] ?? null,
                'source_type' => $payment->getMorphClass(),
                'source_id' => $payment->getKey(),
                'created_by' => $user->getKey(),
            ]);

            $arAfter = 0.0;
            $status = InvoiceStatus::Cancelled->value;

            if ($invoice) {
                $invoice->syncStatus();
                $invoice->refresh();
                $arAfter = $invoice->balance();
                $status = $invoice->status->value;
            }

            $this->audit->log(
                'sales.payment_received',
                $payment,
                'Pembayaran diterima Rp'.number_format((float) $payment->amount, 0).($invoice ? ' untuk '.$invoice->invoice_number : ''),
                $payment->payment_number,
                ['ar_before' => $arBefore, 'ar_after' => $arAfter],
            );

            return [
                'payment' => $payment,
                'invoice' => $invoice?->fresh(),
                'ar_before' => $arBefore,
                'ar_after' => $arAfter,
                'cash_in' => (float) $payment->amount,
                'status' => $status,
            ];
        });
    }

    // ------------------------------------------------------------------
    // Payables, expenses, other income
    // ------------------------------------------------------------------

    /**
     * Pay a supplier, reducing the payable created by a posted goods receipt.
     *
     * @param  array{supplier_id: int, goods_receipt_id?: int|null, payment_date: string, amount: float, method: string, reference_number?: string|null, notes?: string|null}  $data
     * @return array{payment: SupplierPayment, ap_before: float, ap_after: float, cash_out: float}
     */
    public function recordSupplierPayment(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user): array {
            $receipt = isset($data['goods_receipt_id']) && $data['goods_receipt_id']
                ? GoodsReceipt::query()->find($data['goods_receipt_id'])
                : null;

            if ($receipt && (int) $receipt->supplier_id !== (int) $data['supplier_id']) {
                throw new \InvalidArgumentException('Penerimaan barang tidak sesuai dengan pemasok yang dipilih.');
            }

            $apBefore = $receipt?->outstandingAmount() ?? 0.0;

            $payment = SupplierPayment::create([
                'payment_number' => $this->nextNumber('SP', 'supplier_payments'),
                'supplier_id' => $data['supplier_id'],
                'goods_receipt_id' => $receipt?->getKey(),
                'payment_date' => $data['payment_date'],
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user->getKey(),
            ]);

            CashTransaction::create([
                'transaction_number' => $this->nextNumber('TRX', 'cash_transactions'),
                'transaction_date' => $data['payment_date'],
                'direction' => CashDirection::Out,
                'category' => CashCategory::SupplierPayment,
                'amount' => $payment->amount,
                'description' => 'Pembayaran pemasok'.($receipt ? ' '.$receipt->receipt_number : ''),
                'method' => $data['method'],
                'reference_number' => $data['reference_number'] ?? null,
                'source_type' => $payment->getMorphClass(),
                'source_id' => $payment->getKey(),
                'created_by' => $user->getKey(),
            ]);

            $apAfter = $receipt?->fresh()->outstandingAmount() ?? 0.0;

            $this->audit->log(
                'finance.supplier_paid',
                $payment,
                'Pembayaran pemasok Rp'.number_format((float) $payment->amount, 0),
                $payment->payment_number,
                ['ap_before' => $apBefore, 'ap_after' => $apAfter],
            );

            return [
                'payment' => $payment,
                'ap_before' => $apBefore,
                'ap_after' => $apAfter,
                'cash_out' => (float) $payment->amount,
            ];
        });
    }

    /**
     * Record an operating expense together with its cash outflow.
     *
     * @param  array<string, mixed>  $data
     * @return array{expense: Expense, cash_out: float}
     */
    public function recordExpense(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user): array {
            $expense = Expense::create([
                'expense_number' => $this->nextNumber('EXP', 'expenses'),
                'expense_date' => $data['expense_date'],
                'category' => $data['category'],
                'amount' => round((float) $data['amount'], 2),
                'description' => $data['description'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,
                'reference' => $data['reference'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->getKey(),
                'approved_by' => $user->getKey(),
            ]);

            $cash = CashTransaction::create([
                'transaction_number' => $this->nextNumber('TRX', 'cash_transactions'),
                'transaction_date' => $data['expense_date'],
                'direction' => CashDirection::Out,
                'category' => CashCategory::Expense,
                'amount' => $expense->amount,
                'description' => $expense->description,
                'method' => $expense->payment_method,
                'reference_number' => $expense->reference,
                'source_type' => $expense->getMorphClass(),
                'source_id' => $expense->getKey(),
                'created_by' => $user->getKey(),
            ]);

            $expense->update(['cash_transaction_id' => $cash->getKey()]);

            $this->audit->log(
                'finance.expense_recorded',
                $expense,
                "Biaya {$expense->category->label()}: {$expense->description}",
                $expense->expense_number,
                ['amount' => (float) $expense->amount],
            );

            return ['expense' => $expense->fresh(), 'cash_out' => (float) $expense->amount];
        });
    }

    /**
     * Record income outside customer invoicing (manure, livestock disposal, subsidy).
     *
     * @param  array<string, mixed>  $data
     * @return array{income: Income, cash_in: float}
     */
    public function recordIncome(array $data, User $user): array
    {
        return DB::transaction(function () use ($data, $user): array {
            $income = Income::create([
                'income_number' => $this->nextNumber('INC', 'incomes'),
                'income_date' => $data['income_date'],
                'category' => $data['category'],
                'amount' => round((float) $data['amount'], 2),
                'description' => $data['description'],
                'source_name' => $data['source_name'] ?? null,
                'reference' => $data['reference'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->getKey(),
            ]);

            $cash = CashTransaction::create([
                'transaction_number' => $this->nextNumber('TRX', 'cash_transactions'),
                'transaction_date' => $data['income_date'],
                'direction' => CashDirection::In,
                'category' => CashCategory::OtherIncome,
                'amount' => $income->amount,
                'description' => $income->description,
                'method' => $income->payment_method,
                'reference_number' => $income->reference,
                'source_type' => $income->getMorphClass(),
                'source_id' => $income->getKey(),
                'created_by' => $user->getKey(),
            ]);

            $income->update(['cash_transaction_id' => $cash->getKey()]);

            $this->audit->log(
                'finance.income_recorded',
                $income,
                "Pendapatan {$income->category->label()}: {$income->description}",
                $income->income_number,
                ['amount' => (float) $income->amount],
            );

            return ['income' => $income->fresh(), 'cash_in' => (float) $income->amount];
        });
    }

    // ------------------------------------------------------------------
    // Aggregates
    // ------------------------------------------------------------------

    /**
     * Accounts receivable: outstanding balance on non-cancelled invoices.
     */
    public function accountsReceivable(): float
    {
        return round((float) Invoice::query()
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->sum(DB::raw('total - paid_amount')), 2);
    }

    /**
     * Accounts payable: posted goods receipts not yet paid to suppliers.
     */
    public function accountsPayable(): float
    {
        $receipts = GoodsReceipt::query()
            ->where('status', 'posted')
            ->withSum('payments', 'amount')
            ->get();

        return round((float) $receipts->sum(
            fn ($receipt) => max(0, (float) $receipt->total_amount - (float) $receipt->payments_sum_amount)
        ), 2);
    }

    /**
     * Overdue receivable amount.
     */
    public function overdueReceivable(): float
    {
        return round((float) Invoice::query()
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->whereDate('due_date', '<', now()->toDateString())
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->overdueAmount()), 2);
    }

    /**
     * Cash balance from the movement ledger.
     */
    public function cashBalance(): float
    {
        $in = (float) CashTransaction::query()->where('direction', 'in')->sum('amount');
        $out = (float) CashTransaction::query()->where('direction', 'out')->sum('amount');

        return round($in - $out, 2);
    }

    /**
     * Revenue, expenses and estimated profit over a period.
     *
     * @return array{
     *     revenue: float,
     *     other_income: float,
     *     operating_expense: float,
     *     profit_estimate: float,
     *     cash_in: float,
     *     cash_out: float,
     *     net_cash: float
     * }
     */
    public function periodSummary(string $from, string $to): array
    {
        $revenue = (float) Invoice::query()
            ->whereDate('invoice_date', '>=', $from)->whereDate('invoice_date', '<=', $to)
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->sum('total');

        $otherIncome = (float) Income::query()->between($from, $to)->sum('amount');

        $operatingExpense = (float) Expense::query()->between($from, $to)->sum('amount');

        $cashIn = (float) CashTransaction::query()
            ->where('direction', 'in')
            ->whereDate('transaction_date', '>=', $from)->whereDate('transaction_date', '<=', $to)
            ->sum('amount');

        $cashOut = (float) CashTransaction::query()
            ->where('direction', 'out')
            ->whereDate('transaction_date', '>=', $from)->whereDate('transaction_date', '<=', $to)
            ->sum('amount');

        return [
            'revenue' => round($revenue, 2),
            'other_income' => round($otherIncome, 2),
            'operating_expense' => round($operatingExpense, 2),
            'profit_estimate' => round($revenue + $otherIncome - $operatingExpense, 2),
            'cash_in' => round($cashIn, 2),
            'cash_out' => round($cashOut, 2),
            'net_cash' => round($cashIn - $cashOut, 2),
        ];
    }

    private function nextInvoiceNumber(): string
    {
        return $this->nextNumber('INV', 'invoices');
    }

    /**
     * Sequential human-readable document number.
     */
    private function nextNumber(string $prefix, string $table): string
    {
        $column = self::NUMBER_COLUMNS[$table] ?? 'number';

        $latest = DB::table($table)
            ->where($column, 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value($column);

        $sequence = $latest ? ((int) Str::afterLast((string) $latest, '-')) + 1 : 1;

        return $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /** @var array<string, string> */
    private const NUMBER_COLUMNS = [
        'invoices' => 'invoice_number',
        'payments' => 'payment_number',
        'supplier_payments' => 'payment_number',
        'cash_transactions' => 'transaction_number',
        'expenses' => 'expense_number',
        'incomes' => 'income_number',
    ];

    public function customersWithBalances(): array
    {
        return Customer::query()
            ->where('is_active', true)
            ->withSum(['invoices as balance_sum' => fn ($q) => $q
                ->where('status', '!=', InvoiceStatus::Cancelled->value)
                ->selectRaw('total - paid_amount')], 'balance_sum')
            ->orderByDesc('balance_sum')
            ->get()
            ->map(fn (Customer $customer) => [
                'customer' => $customer,
                'balance' => round((float) $customer->balance_sum, 2),
            ])
            ->all();
    }

    public function supplierOutstanding(): array
    {
        return Supplier::query()
            ->where('is_active', true)
            ->get()
            ->map(function (Supplier $supplier) {
                $receipts = $supplier->goodsReceipts()->where('status', 'posted')->withSum('payments', 'amount')->get();

                $outstanding = $receipts->sum(
                    fn ($receipt) => max(0, (float) $receipt->total_amount - (float) $receipt->payments_sum_amount)
                );

                return ['supplier' => $supplier, 'outstanding' => round((float) $outstanding, 2)];
            })
            ->filter(fn ($row) => $row['outstanding'] > 0)
            ->sortByDesc('outstanding')
            ->values()
            ->all();
    }
}
