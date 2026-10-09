<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\SalesOrderStatus;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sales, receivables, payables and cash movements.
 *
 * Scenarios 3, 4 and 5 from the specification:
 *   - an invoice on Net 30 terms raises revenue and receivable, cash is unchanged;
 *   - a payment reduces receivable and increases cash;
 *   - an invoice past its due date is reported as overdue.
 */
class SalesFinanceSeeder extends Seeder
{
    public function run(FinanceService $finance): void
    {
        DB::transaction(function () use ($finance): void {
            $manager = User::where('email', 'manager@farm.test')->firstOrFail();
            $financeUser = User::where('email', 'finance@farm.test')->firstOrFail();

            $products = $this->products();
            $customers = $this->customers();

            $this->expenses($financeUser);
            $this->otherIncome($financeUser);

            // Scenario 3 + 4 + 5 mix: one paid, one partly paid, one overdue.
            $this->invoiced($finance, $manager, $customers, $products);
        });

        $this->command?->info('Pelanggan, produk, pesanan, faktur, pembayaran, biaya, dan arus kas dibuat.');
    }

    /** @return array<string, Product> */
    private function products(): array
    {
        return [
            'milk' => Product::create([
                'code' => 'PRD-SUSU', 'name' => 'Susu Segar', 'unit' => 'liter',
                'default_price' => 12_000, 'category' => 'milk', 'is_active' => true,
            ]),
            'pasteurised' => Product::create([
                'code' => 'PRD-PAS', 'name' => 'Susu Pasteurisasi 1 L', 'unit' => 'liter',
                'default_price' => 16_000, 'category' => 'milk', 'is_active' => true,
            ]),
            'yoghurt' => Product::create([
                'code' => 'PRD-YOG', 'name' => 'Yoghurt', 'unit' => 'cup',
                'default_price' => 6_000, 'category' => 'milk', 'is_active' => true,
            ]),
            'manure' => Product::create([
                'code' => 'PRD-PK', 'name' => 'Pupuk Kotor', 'unit' => 'kg',
                'default_price' => 1_500, 'category' => 'byproduct', 'is_active' => true,
            ]),
        ];
    }

    /** @return array<string, Customer> */
    private function customers(): array
    {
        $definitions = [
            ['CUS-001', 'Koperasi Susu Sejahtera', 'partner', 30, 150_000_000],
            ['CUS-002', 'Toko Segar Jaya', 'wholesale', 14, 60_000_000],
            ['CUS-003', 'Warung Sumber ASI', 'retail', 0, 5_000_000],
            ['CUS-004', 'CV Dairy Grosir', 'wholesale', 7, 75_000_000],
            ['CUS-005', 'Rumah Susu Kita', 'retail', 0, 10_000_000],
        ];

        $customers = [];

        foreach ($definitions as [$code, $name, $type, $terms, $limit]) {
            $customers[$code] = Customer::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'contact_person' => fake()->name(),
                'phone' => '0812'.fake()->numerify('########'),
                'email' => fake()->unique()->safeEmail(),
                'city' => fake()->randomElement(['Sidoarjo', 'Surabaya', 'Malang', 'Gresik']),
                'credit_limit' => $limit,
                'payment_terms_days' => $terms,
                'is_active' => true,
            ]);
        }

        return $customers;
    }

    private function expenses(User $financeUser): void
    {
        $supplier = Supplier::query()->first();

        $definitions = [
            ['veterinary', 1_450_000, 'Pembelian obat dan vitamin hewan', 4],
            ['labor', 9_600_000, 'Gaji 4 orang tenaga kerja', 6],
            ['utilities', 2_350_000, 'Listrik kandang dan gudang', 5],
            ['maintenance', 1_800_000, 'Perbaikan atap gudang pakan', 9],
            ['transportation', 850_000, 'BBM kendaraan angkut susu', 2],
            ['equipment', 4_200_000, 'Pompa air dan selang', 14],
            ['feed', 7_500_000, 'Pakan konsentrat pelengkap', 7],
            ['labor', 9_600_000, 'Gaji 4 orang tenaga kerja', 20],
            ['utilities', 2_100_000, 'Listrik bulan berjalan', 18],
        ];

        foreach ($definitions as $index => [$category, $amount, $description, $daysAgo]) {
            $expense = Expense::create([
                'expense_number' => 'EXP-'.now()->subDays($daysAgo)->format('Ymd').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'expense_date' => now()->subDays($daysAgo)->toDateString(),
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'supplier_id' => $category === 'equipment' ? $supplier?->id : null,
                'vendor_name' => $category === 'equipment' ? null : fake()->company(),
                'reference' => 'NOTA-'.fake()->numerify('#####'),
                'payment_method' => fake()->randomElement(PaymentMethod::values()),
                'created_by' => $financeUser->id,
                'approved_by' => $financeUser->id,
            ]);

            $cash = CashTransaction::create([
                'transaction_number' => 'TRX-'.now()->subDays($daysAgo)->format('Ymd').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'transaction_date' => $expense->expense_date->toDateString(),
                'direction' => 'out',
                'category' => 'expense',
                'amount' => $expense->amount,
                'description' => $expense->description,
                'method' => $expense->payment_method,
                'reference_number' => $expense->reference,
                'source_type' => $expense->getMorphClass(),
                'source_id' => $expense->id,
                'created_by' => $financeUser->id,
            ]);

            $expense->update(['cash_transaction_id' => $cash->id]);
        }
    }

    private function otherIncome(User $financeUser): void
    {
        $definitions = [
            ['manure', 3_000_000, 'Penjualan 2 ton pupuk', 'CV Tani Hijau', 12],
            ['subsidy', 5_000_000, 'Bantuan 향상 mutu stunggas', 'Pemohon Kabupaten', 25],
            ['other_sale', 1_200_000, 'Penjualan lain-lain', 'Pembeli lokal', 8],
        ];

        foreach ($definitions as $index => [$category, $amount, $description, $source, $daysAgo]) {
            $income = Income::create([
                'income_number' => 'INC-'.now()->subDays($daysAgo)->format('Ymd').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'income_date' => now()->subDays($daysAgo)->toDateString(),
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'source_name' => $source,
                'reference' => 'REF-'.fake()->numerify('#####'),
                'payment_method' => 'cash',
                'created_by' => $financeUser->id,
            ]);

            $cash = CashTransaction::create([
                'transaction_number' => 'TRX-'.now()->subDays($daysAgo)->format('Ymd').'-'.str_pad((string) ($index + 101), 4, '0', STR_PAD_LEFT),
                'transaction_date' => $income->income_date->toDateString(),
                'direction' => 'in',
                'category' => 'other_income',
                'amount' => $income->amount,
                'description' => $income->description,
                'method' => 'cash',
                'reference_number' => $income->reference,
                'source_type' => $income->getMorphClass(),
                'source_id' => $income->id,
                'created_by' => $financeUser->id,
            ]);

            $income->update(['cash_transaction_id' => $cash->id]);
        }

        // Owner draw.
        CashTransaction::create([
            'transaction_number' => 'TRX-'.now()->subDays(3)->format('Ymd').'-0201',
            'transaction_date' => now()->subDays(3)->toDateString(),
            'direction' => 'out',
            'category' => 'owner_draw',
            'amount' => 5_000_000,
            'description' => 'Prive pemilik bulan ini',
            'method' => 'transfer',
            'created_by' => $financeUser->id,
        ]);
    }

    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, Product>  $products
     */
    private function invoiced(FinanceService $finance, User $manager, array $customers, array $products): void
    {
        $scenario = [
            // Paid in full, Net 30.
            ['customer' => 'CUS-001', 'days' => 12, 'terms' => 30, 'lines' => [
                ['product' => 'milk', 'quantity' => 800, 'price' => 12_000],
                ['product' => 'pasteurised', 'quantity' => 120, 'price' => 16_000],
            ], 'pay' => 'full'],
            // Partly paid, Net 30.
            ['customer' => 'CUS-002', 'days' => 9, 'terms' => 30, 'lines' => [
                ['product' => 'milk', 'quantity' => 450, 'price' => 11_800],
            ], 'pay' => 'partial'],
            // Overdue, Net 14.
            ['customer' => 'CUS-004', 'days' => 52, 'terms' => 14, 'lines' => [
                ['product' => 'milk', 'quantity' => 300, 'price' => 12_200],
                ['product' => 'yoghurt', 'quantity' => 200, 'price' => 6_000],
            ], 'pay' => 'none'],
            // Overdue and partly paid, Net 30.
            ['customer' => 'CUS-003', 'days' => 45, 'terms' => 7, 'lines' => [
                ['product' => 'milk', 'quantity' => 60, 'price' => 12_000],
            ], 'pay' => 'partial'],
            // Current, Net 30, not yet due.
            ['customer' => 'CUS-005', 'days' => 3, 'terms' => 30, 'lines' => [
                ['product' => 'milk', 'quantity' => 40, 'price' => 12_500],
            ], 'pay' => 'none'],
        ];

        $sequence = 1;

        foreach ($scenario as $entry) {
            $customer = $customers[$entry['customer']];
            $invoiceDate = now()->subDays($entry['days'])->toDateString();

            $order = SalesOrder::create([
                'so_number' => 'SO-'.now()->subDays($entry['days'])->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'order_date' => $invoiceDate,
                'status' => SalesOrderStatus::Delivered,
                'notes' => 'Pengiriman rutin harian.',
                'created_by' => $manager->id,
            ]);

            foreach ($entry['lines'] as $line) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $products[$line['product']]->id,
                    'description' => $products[$line['product']]->name,
                    'quantity' => $line['quantity'],
                    'unit' => $products[$line['product']]->unit->value,
                    'unit_price' => $line['price'],
                ]);
            }

            $order->recalculateTotals();
            $order->update(['status' => SalesOrderStatus::Delivered]);

            $result = $finance->createInvoice([
                'customer_id' => $customer->id,
                'sales_order_id' => $order->id,
                'invoice_date' => $invoiceDate,
                'payment_terms_days' => $entry['terms'],
                'subtotal' => (float) $order->subtotal,
                'notes' => 'Termin '.$entry['terms'].' hari.',
            ], $manager);

            $invoice = $result['invoice'];

            if ($entry['pay'] !== 'none') {
                $amount = $entry['pay'] === 'full'
                    ? (float) $invoice->total
                    : round((float) $invoice->total * 0.4, -3);

                $finance->recordPayment([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'payment_date' => now()->subDays(max(1, $entry['days'] - random_int(3, 8)))->toDateString(),
                    'method' => $entry['pay'] === 'full' ? 'transfer' : 'cash',
                    'reference_number' => 'TRX-'.fake()->bothify('??######'),
                ], $manager);
            }

            $sequence++;
        }
    }
}
