<?php

namespace Database\Seeders;

use App\Enums\SalesOrderStatus;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sales, receivables, payables and cash movements.
 *
 * The farm sells one thing: fresh cow's milk. The product catalogue contains
 * only that product, and every seeded sales-order line points to it. The
 * invoices cover a paid account, a partly paid account, a current account and
 * an overdue account so the receivable pages show real states rather than five
 * copies of the same transaction.
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

            $this->expenses($finance, $financeUser);
            $this->otherIncome($finance, $financeUser);
            $this->cashMovements($financeUser);
            $this->salesAndInvoices($finance, $manager, $customers, $products);
        });

        $this->command?->info('Pelanggan, produk susu sapi, pesanan, faktur, pembayaran, biaya, dan arus kas dibuat.');
    }

    /** @return array<string, Product> */
    private function products(): array
    {
        return [
            'milk' => Product::create([
                'code' => Product::COW_MILK_CODE,
                'name' => 'Susu Sapi Segar',
                'unit' => 'liter',
                'default_price' => 12_000,
                'category' => 'milk',
                'is_active' => true,
                'notes' => 'Susu sapi segar murni dari pemerahan pagi dan sore. Dijual per liter.',
            ]),
        ];
    }

    /** @return array<string, Customer> */
    private function customers(): array
    {
        $definitions = [
            ['CUS-001', 'Koperasi Susu Sejahtera', 'partner', 30, 150_000_000, 'Ibu Sumiati', '081245670011', 'Gresik', 'Jl. Raya Gresik No. 12'],
            ['CUS-002', 'Toko Segar Jaya', 'wholesale', 14, 100_000_000, 'Bapak Haryanto', '081245670012', 'Sidoarjo', 'Jl. Raya Sidoarjo No. 45'],
            ['CUS-003', 'Warung Sumber ASI', 'retail', 0, 25_000_000, 'Ibu Endah', '081245670013', 'Sidoarjo', 'Jl. Ahmad Yani No. 8'],
            ['CUS-004', 'CV Dairy Grosir', 'wholesale', 7, 150_000_000, 'Bapak Sutrisno', '081245670014', 'Gresik', 'Jl. Industri No. 21'],
            ['CUS-005', 'Rumah Susu Kita', 'retail', 0, 20_000_000, 'Ibu Lestari', '081245670015', 'Sidoarjo', 'Jl. Diponegoro No. 17'],
            ['CUS-006', 'KUD Sumber Makmur', 'partner', 30, 250_000_000, 'Bapak Samsul', '081245670016', 'Malang', 'Jl. Raya Pujon No. 5'],
            ['CUS-007', 'Kafe Susu Pagi', 'retail', 0, 10_000_000, 'Ibu Nia', '081245670017', 'Malang', 'Jl. Soekarno-Hatta No. 33'],
        ];

        $customers = [];

        foreach ($definitions as [$code, $name, $type, $terms, $limit, $contact, $phone, $city, $address]) {
            $slug = Str::of($name)->lower()->replaceMatches('/[^a-z]+/', '-')->trim('-')->value();

            $customers[$code] = Customer::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'contact_person' => $contact,
                'phone' => $phone,
                'email' => $slug.'@contoh.id',
                'address' => $address,
                'city' => $city,
                'credit_limit' => $limit,
                'payment_terms_days' => $terms,
                'is_active' => true,
                'notes' => 'Pelanggan tetap; pembayaran mengikuti termin yang disepakati.',
            ]);
        }

        return $customers;
    }

    private function expenses(FinanceService $finance, User $financeUser): void
    {
        $feedSupplier = Supplier::where('code', 'SUP-001')->first();
        $equipmentSupplier = Supplier::where('code', 'SUP-003')->first();

        $definitions = [
            ['feed', 100_000_000, 'Pembelian konsentrat dan dedak padi', 2, $feedSupplier?->id, 'CV Sumber Pakan Sejahtera', 'transfer', 'INV-PAKAN-2601'],
            ['veterinary', 4_500_000, 'Obat mastitis, vitamin, dan alat suntik', 4, null, 'Apotek Hewan Medika', 'cash', 'NOTA-AP-104'],
            ['labor', 18_000_000, 'Gaji empat tenaga kandang', 6, null, 'Koperasi Karyawan Suka Maju', 'transfer', 'PAYROLL-2601'],
            ['utilities', 5_500_000, 'Listrik kandang dan gudang', 5, null, 'PLN Unit Malang', 'transfer', 'PLN-2601-887'],
            ['maintenance', 3_500_000, 'Perbaikan atap gudang pakan', 9, null, 'Bengkel Sumber Jaya', 'cash', 'NOTA-BSJ-052'],
            ['transportation', 2_500_000, 'BBM kendaraan angkut susu', 2, null, 'SPBU 54.651.01', 'cash', 'NOTA-SPBU-771'],
            ['equipment', 6_500_000, 'Pompa air dan selang kandang', 14, $equipmentSupplier?->id, 'CV Sinar Mandiri Perlengkapan', 'transfer', 'INV-SMP-118'],
            ['feed', 45_000_000, 'Pakan konsentrat pelengkap', 7, $feedSupplier?->id, 'CV Sumber Pakan Sejahtera', 'transfer', 'INV-PAKAN-2598'],
            ['labor', 18_000_000, 'Gaji empat tenaga kandang bulan lalu', 20, null, 'Koperasi Karyawan Suka Maju', 'transfer', 'PAYROLL-2599'],
            ['utilities', 5_000_000, 'Listrik bulan berjalan', 18, null, 'PLN Unit Malang', 'transfer', 'PLN-2599-771'],
            ['other', 1_200_000, 'Administrasi dan materai dokumen', 12, null, 'Kantor Pos Malang', 'cash', 'NOTA-KP-031'],
            ['maintenance', 2_500_000, 'Perawatan pagar dan saluran air', 30, null, 'Tukang Bangunan Pak Slamet', 'cash', 'NOTA-TB-014'],
        ];

        foreach ($definitions as [$category, $amount, $description, $daysAgo, $supplierId, $vendor, $method, $reference]) {
            $finance->recordExpense([
                'expense_date' => now()->subDays($daysAgo)->toDateString(),
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'supplier_id' => $supplierId,
                'vendor_name' => $vendor,
                'reference' => $reference,
                'payment_method' => $method,
                'notes' => 'Biaya operasional peternakan.',
            ], $financeUser);
        }
    }

    private function otherIncome(FinanceService $finance, User $financeUser): void
    {
        $definitions = [
            ['subsidy', 5_000_000, 'Bantuan peningkatan mutu susu', 'Dinas Peternakan Kabupaten Malang', 25, 'transfer', 'SK-DISNAK-2601'],
            ['other', 1_250_000, 'Insentif kualitas susu', 'Koperasi Susu Sejahtera', 12, 'transfer', 'INS-KUD-2601'],
            ['other', 2_000_000, 'Klaim asuransi ternak', 'Asuransi Ternak Nusantara', 40, 'transfer', 'KLAIM-ATN-118'],
        ];

        foreach ($definitions as [$category, $amount, $description, $source, $daysAgo, $method, $reference]) {
            $finance->recordIncome([
                'income_date' => now()->subDays($daysAgo)->toDateString(),
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'source_name' => $source,
                'reference' => $reference,
                'payment_method' => $method,
                'notes' => 'Pendapatan di luar penjualan susu kepada pelanggan.',
            ], $financeUser);
        }
    }

    private function cashMovements(User $financeUser): void
    {
        // The farm started the period with working capital from the owner. It
        // is a cash inflow, not sales revenue, so it is not recorded as income.
        CashTransaction::create([
            'transaction_number' => 'TRX-'.now()->subDays(60)->format('Ymd').'-0101',
            'transaction_date' => now()->subDays(60)->toDateString(),
            'direction' => 'in',
            'category' => 'other_income',
            'amount' => 50_000_000,
            'description' => 'Setoran modal awal pemilik untuk modal kerja',
            'method' => 'transfer',
            'reference_number' => 'MODAL-2601',
            'created_by' => $financeUser->id,
        ]);

        CashTransaction::create([
            'transaction_number' => 'TRX-'.now()->subDays(3)->format('Ymd').'-0201',
            'transaction_date' => now()->subDays(3)->toDateString(),
            'direction' => 'out',
            'category' => 'owner_draw',
            'amount' => 5_000_000,
            'description' => 'Prive pemilik untuk kebutuhan pribadi',
            'method' => 'transfer',
            'reference_number' => 'TRF-PRIVE-2601',
            'created_by' => $financeUser->id,
        ]);

        CashTransaction::create([
            'transaction_number' => 'TRX-'.now()->subDays(50)->format('Ymd').'-0301',
            'transaction_date' => now()->subDays(50)->toDateString(),
            'direction' => 'in',
            'category' => 'loan',
            'amount' => 25_000_000,
            'description' => 'Pencairan kredit modal kerja Bank Jatim',
            'method' => 'transfer',
            'reference_number' => 'KREDIT-BJT-2601',
            'created_by' => $financeUser->id,
        ]);
    }

    /**
     * @param  array<string, Customer>  $customers
     * @param  array<string, Product>  $products
     */
    private function salesAndInvoices(
        FinanceService $finance,
        User $manager,
        array $customers,
        array $products,
    ): void {
        $milk = $products['milk'];

        $scenario = [
            // Paid in full, Net 30.
            ['customer' => 'CUS-001', 'days' => 12, 'terms' => 30, 'quantity' => 7_000, 'price' => 12_000, 'status' => SalesOrderStatus::Delivered, 'pay' => 'full'],
            // Partly paid, Net 30.
            ['customer' => 'CUS-002', 'days' => 9, 'terms' => 30, 'quantity' => 4_000, 'price' => 11_800, 'status' => SalesOrderStatus::Delivered, 'pay' => 'partial'],
            // Overdue, Net 14, unpaid.
            ['customer' => 'CUS-004', 'days' => 52, 'terms' => 14, 'quantity' => 10_000, 'price' => 12_200, 'status' => SalesOrderStatus::Delivered, 'pay' => 'none'],
            // Overdue and partly paid, Net 7.
            ['customer' => 'CUS-003', 'days' => 45, 'terms' => 7, 'quantity' => 1_500, 'price' => 12_000, 'status' => SalesOrderStatus::Delivered, 'pay' => 'partial'],
            // Current, Net 30, not yet due.
            ['customer' => 'CUS-005', 'days' => 3, 'terms' => 30, 'quantity' => 1_000, 'price' => 12_500, 'status' => SalesOrderStatus::Delivered, 'pay' => 'none'],
            // Large cooperative order, paid in full.
            ['customer' => 'CUS-006', 'days' => 20, 'terms' => 30, 'quantity' => 8_000, 'price' => 11_500, 'status' => SalesOrderStatus::Delivered, 'pay' => 'full'],
            // Confirmed but not invoiced yet.
            ['customer' => 'CUS-007', 'days' => 1, 'terms' => 0, 'quantity' => 300, 'price' => 13_000, 'status' => SalesOrderStatus::Confirmed, 'pay' => 'none'],
            // Draft order for tomorrow's delivery.
            ['customer' => 'CUS-003', 'days' => 0, 'terms' => 0, 'quantity' => 250, 'price' => 13_000, 'status' => SalesOrderStatus::Draft, 'pay' => 'none'],
            // Delivered and invoiced, partly paid.
            ['customer' => 'CUS-004', 'days' => 6, 'terms' => 7, 'quantity' => 2_000, 'price' => 12_000, 'status' => SalesOrderStatus::Delivered, 'pay' => 'partial'],
            // Delivered, not yet invoiced: the receivable action is still open.
            ['customer' => 'CUS-006', 'days' => 14, 'terms' => 30, 'quantity' => 3_500, 'price' => 11_600, 'status' => SalesOrderStatus::Delivered, 'pay' => 'uninvoiced'],
            // Cancelled before delivery.
            ['customer' => 'CUS-001', 'days' => 8, 'terms' => 30, 'quantity' => 1_000, 'price' => 12_000, 'status' => SalesOrderStatus::Cancelled, 'pay' => 'none'],
        ];

        $sequence = 1;

        foreach ($scenario as $entry) {
            $customer = $customers[$entry['customer']];
            $orderDate = now()->subDays($entry['days']);

            $order = SalesOrder::create([
                'so_number' => 'SO-'.$orderDate->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'order_date' => $orderDate->toDateString(),
                'status' => $entry['status'],
                'notes' => $entry['status'] === SalesOrderStatus::Cancelled
                    ? 'Dibatalkan karena jadwal pengiriman bentrok.'
                    : 'Pengiriman susu sapi segar sesuai jadwal harian.',
                'created_by' => $manager->id,
            ]);

            SalesOrderItem::create([
                'sales_order_id' => $order->id,
                'product_id' => $milk->id,
                'description' => 'Susu sapi segar',
                'quantity' => $entry['quantity'],
                'unit' => $milk->unit->value,
                'unit_price' => $entry['price'],
                'notes' => 'Hasil pemerahan pagi dan sore.',
            ]);

            $order->recalculateTotals();

            if (in_array($entry['pay'], ['uninvoiced', 'none'], true) && $entry['status'] !== SalesOrderStatus::Delivered) {
                $sequence++;

                continue;
            }

            if ($entry['pay'] === 'uninvoiced') {
                $sequence++;

                continue;
            }

            $result = $finance->createInvoice([
                'customer_id' => $customer->id,
                'sales_order_id' => $order->id,
                'invoice_date' => $orderDate->toDateString(),
                'payment_terms_days' => $entry['terms'],
                'subtotal' => (float) $order->subtotal,
                'notes' => 'Termin pembayaran '.$entry['terms'].' hari.',
            ], $manager);

            $invoice = $result['invoice'];

            if ($entry['pay'] === 'full' || $entry['pay'] === 'partial') {
                $amount = $entry['pay'] === 'full'
                    ? (float) $invoice->total
                    : round((float) $invoice->total * 0.4, -3);

                $paymentDate = now()->subDays(max(1, $entry['days'] - 4));

                $finance->recordPayment([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'payment_date' => $paymentDate->toDateString(),
                    'method' => $entry['pay'] === 'full' ? 'transfer' : 'cash',
                    'reference_number' => 'TRF-'.$paymentDate->format('ymd').'-'.str_pad((string) ($sequence + 20), 4, '0', STR_PAD_LEFT),
                    'notes' => $entry['pay'] === 'full'
                        ? 'Pelunasan faktur.'
                        : 'Pembayaran sebagian; sisa tercatat sebagai piutang.',
                ], $manager);
            }

            $sequence++;
        }
    }
}
