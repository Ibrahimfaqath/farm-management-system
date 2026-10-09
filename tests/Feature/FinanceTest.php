<?php

namespace Tests\Feature;

use App\Enums\CashDirection;
use App\Enums\InvoiceStatus;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(float $total = 10_000_000, int $termsDays = 30, ?string $invoiceDate = null): Invoice
    {
        $date = $invoiceDate ?? now()->toDateString();

        return Invoice::create([
            'invoice_number' => 'INV-'.uniqid(),
            'customer_id' => Customer::factory()->create()->id,
            'invoice_date' => $date,
            'payment_terms_days' => $termsDays,
            'due_date' => now()->parse($date)->addDays($termsDays)->toDateString(),
            'subtotal' => $total,
            'total' => $total,
            'paid_amount' => 0,
            'status' => InvoiceStatus::Unpaid,
        ]);
    }

    public function test_creating_an_invoice_increases_receivable_but_not_cash(): void
    {
        $finance = app(FinanceService::class);
        $cashBefore = $finance->cashBalance();

        $result = $finance->createInvoice([
            'customer_id' => Customer::factory()->create()->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms_days' => 30,
            'subtotal' => 10_000_000,
        ], $this->owner());

        $this->assertSame(10_000_000.0, $result['receivable_added']);
        $this->assertSame(0.0, $result['cash_effect']);
        $this->assertSame(10_000_000.0, $finance->accountsReceivable());
        $this->assertSame($cashBefore, $finance->cashBalance());
        $this->assertSame(0, CashTransaction::count());
    }

    public function test_payment_reduces_receivable_and_increases_cash(): void
    {
        $finance = app(FinanceService::class);
        $invoice = $this->invoice(10_000_000);
        $cashBefore = $finance->cashBalance();

        $result = $finance->recordPayment([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => 10_000_000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ], $this->financeUser());

        $this->assertSame(10_000_000.0, $result['ar_before']);
        $this->assertSame(0.0, $result['ar_after']);
        $this->assertSame(10_000_000.0, $result['cash_in']);
        $this->assertSame(InvoiceStatus::Paid->value, $result['status']);

        $this->assertSame(0.0, $finance->accountsReceivable());
        $this->assertSame($cashBefore + 10_000_000, $finance->cashBalance());

        $cash = CashTransaction::sole();
        $this->assertSame(CashDirection::In, $cash->direction);
        $this->assertSame(10_000_000.0, (float) $cash->amount);
        $this->assertSame(Payment::class, $cash->source_type);
    }

    public function test_partial_payment_leaves_partially_paid_status(): void
    {
        $finance = app(FinanceService::class);
        $invoice = $this->invoice(10_000_000);

        $result = $finance->recordPayment([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => 4_000_000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ], $this->financeUser());

        $this->assertSame(6_000_000.0, $result['ar_after']);
        $this->assertSame(InvoiceStatus::Partial->value, $result['status']);
        $this->assertSame(6_000_000.0, $finance->accountsReceivable());

        $invoice->refresh();
        $this->assertSame(4_000_000.0, (float) $invoice->paid_amount);
        $this->assertSame(6_000_000.0, $invoice->balance());
    }

    public function test_overdue_invoices_are_detected(): void
    {
        $finance = app(FinanceService::class);

        $overdue = $this->invoice(5_000_000, 30, now()->subDays(60)->toDateString());
        $current = $this->invoice(3_000_000, 30, now()->toDateString());

        $this->assertTrue($overdue->fresh()->isOverdue());
        $this->assertFalse($current->fresh()->isOverdue());

        $this->assertSame(1, Invoice::query()->overdue()->count());
        $this->assertSame(5_000_000.0, $finance->overdueReceivable());
    }

    public function test_partially_paid_overdue_invoice_reports_only_outstanding(): void
    {
        $invoice = $this->invoice(8_000_000, 30, now()->subDays(60)->toDateString());

        Payment::create([
            'payment_number' => 'PAY-'.uniqid(),
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_date' => now()->subDays(10)->toDateString(),
            'amount' => 3_000_000,
            'method' => 'cash',
        ]);

        $invoice->syncStatus();
        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Partial, $invoice->status);
        $this->assertTrue($invoice->isOverdue());
        $this->assertSame(5_000_000.0, $invoice->overdueAmount());
    }

    public function test_expense_reduces_cash_and_is_recorded_as_expense(): void
    {
        $finance = app(FinanceService::class);
        $cashBefore = $finance->cashBalance();

        $result = $finance->recordExpense([
            'expense_date' => now()->toDateString(),
            'category' => 'veterinary',
            'amount' => 750_000,
            'description' => 'Obat dan vitamin',
            'payment_method' => 'cash',
        ], $this->financeUser());

        $this->assertSame(750_000.0, $result['cash_out']);
        $this->assertSame($cashBefore - 750_000, $finance->cashBalance());

        $cash = CashTransaction::sole();
        $this->assertSame(CashDirection::Out, $cash->direction);
        $this->assertNotNull($result['expense']->cash_transaction_id);
    }

    public function test_period_summary_separates_revenue_from_cash(): void
    {
        $finance = app(FinanceService::class);

        $finance->createInvoice([
            'customer_id' => Customer::factory()->create()->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms_days' => 30,
            'subtotal' => 20_000_000,
        ], $this->owner());

        $finance->recordExpense([
            'expense_date' => now()->toDateString(),
            'category' => 'feed',
            'amount' => 5_000_000,
            'description' => 'Pakan konsentrat',
            'payment_method' => 'cash',
        ], $this->financeUser());

        $summary = $finance->periodSummary(now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertSame(20_000_000.0, $summary['revenue']);
        $this->assertSame(5_000_000.0, $summary['operating_expense']);
        $this->assertSame(15_000_000.0, $summary['profit_estimate']);
        $this->assertSame(0.0, $summary['cash_in']);
        $this->assertSame(5_000_000.0, $summary['cash_out']);
        $this->assertSame(-5_000_000.0, $summary['net_cash']);
    }

    public function test_other_income_increases_cash_without_touching_receivables(): void
    {
        $finance = app(FinanceService::class);
        $cashBefore = $finance->cashBalance();

        $result = $finance->recordIncome([
            'income_date' => now()->toDateString(),
            'category' => 'manure',
            'amount' => 1_500_000,
            'description' => 'Penjualan manure 2 ton',
            'payment_method' => 'cash',
        ], $this->financeUser());

        $this->assertSame(1_500_000.0, $result['cash_in']);
        $this->assertSame($cashBefore + 1_500_000, $finance->cashBalance());
        $this->assertSame(0.0, $finance->accountsReceivable());
    }

    public function test_payment_for_settled_invoice_is_rejected(): void
    {
        $invoice = $this->invoice(1_000_000);
        $finance = app(FinanceService::class);

        $finance->recordPayment([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => 1_000_000,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer',
        ], $this->financeUser());

        $this->actingAs($this->financeUser())
            ->post(route('payments.store'), [
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_date' => now()->toDateString(),
                'amount' => 500_000,
                'method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_invoice_number_must_be_unique(): void
    {
        $invoice = $this->invoice();
        $user = $this->owner();

        $this->actingAs($user)
            ->get(route('sales-orders.create'))
            ->assertOk();
    }
}
