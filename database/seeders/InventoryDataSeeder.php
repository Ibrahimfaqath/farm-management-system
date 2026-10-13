<?php

namespace Database\Seeders;

use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Barn;
use App\Models\CashTransaction;
use App\Models\Farm;
use App\Models\FeedItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\FeedConsumptionService;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Procurement and consumption history.
 *
 * Scenario 1 and 2 from the specification are reproduced here: a purchase order
 * for 5,000 kg is only partially received (4,000 kg), the remaining 1,000 kg
 * stays outstanding, and the 800 kg that is then consumed leaves a traceable
 * batch balance.
 */
class InventoryDataSeeder extends Seeder
{
    public function run(InventoryService $inventory, FeedConsumptionService $consumption): void
    {
        $manager = User::where('email', 'manager@farm.test')->firstOrFail();
        $worker = User::where('email', 'worker@farm.test')->firstOrFail();
        $owner = User::where('email', 'owner@farm.test')->firstOrFail();
        $farm = Farm::firstOrFail();

        DB::transaction(function () use ($inventory, $consumption, $manager, $worker, $owner, $farm): void {
            $feedItems = $this->feedItems();
            $suppliers = $this->suppliers();

            $this->scenarioPartialReceipt($inventory, $feedItems, $suppliers, $manager, $owner);

            $this->openingBalances($inventory, $feedItems, $owner);

            $this->consumptionHistory($consumption, $farm, $feedItems, $manager, $worker);

            $supplierPayment = SupplierPayment::create([
                'payment_number' => 'SP-'.now()->subDays(6)->format('Ymd').'-0001',
                'supplier_id' => $suppliers[0]->id,
                'goods_receipt_id' => GoodsReceipt::query()->orderBy('id')->value('id'),
                'payment_date' => now()->subDays(6)->toDateString(),
                'amount' => 12_000_000,
                'method' => 'transfer',
                'reference_number' => 'TRX-BANK-77812',
                'recorded_by' => $owner->id,
            ]);

            CashTransaction::create([
                'transaction_number' => 'TRX-'.now()->subDays(6)->format('Ymd').'-0001',
                'transaction_date' => now()->subDays(6)->toDateString(),
                'direction' => 'out',
                'category' => 'supplier_payment',
                'amount' => $supplierPayment->amount,
                'description' => 'Pembayaran pemasok '.$supplierPayment->goods_receipt_id,
                'method' => 'transfer',
                'reference_number' => 'TRX-BANK-77812',
                'source_type' => $supplierPayment->getMorphClass(),
                'source_id' => $supplierPayment->id,
                'created_by' => $owner->id,
            ]);

            $this->command?->info('Stok awal, PO, penerimaan sebagian, dan pemakaian selesai.');
        });
    }

    /** @return array<string, FeedItem> */
    private function feedItems(): array
    {
        // One axis: what the ration uses the item for. The list is ordered the
        // way a ration is built — fibre, then energy and protein, then the
        // supplements fed alongside, then anything else.
        //
        // Names are the ones an Indonesian dairy actually buys. "Beras Padi"
        // (paddy rice) was the previous entry: rice is not fed to dairy cattle,
        // its bran is, and the bran is a concentrate rather than a by-product
        // for ration purposes.
        $definitions = [
            // Pakan Serat
            ['FD-HPT01', 'Hay Padi', 'forage', 'kg', 5_400, 1_500, 4_000, 'Gudang Pakan A', 150],
            ['FD-JTG01', 'Jerami Padi', 'forage', 'kg', 3_200, 1_200, 3_500, 'Gudang Pakan A', 120],
            ['FD-SLS01', 'Silase Jagung', 'forage', 'kg', 3_500, 3_000, 5_000, 'Silo Pakan 1', 120],
            ['FD-SLS02', 'Silase Rumput Sorgum', 'forage', 'kg', 3_800, 1_000, 3_000, 'Silo Pakan 2', 120],
            ['FD-RMP02', 'Rumput Field', 'forage', 'kg', 2_600, 800, 2_500, 'Padang Rumput', 60],

            // Konsentrat
            ['FD-KNS01', 'Konsentrat Sapi 8-16', 'concentrate', 'kg', 11_500, 800, 2_500, 'Gudang Pakan B', 90],
            ['FD-KNS02', 'Konsentrat Sapi Awal Laktasi', 'concentrate', 'kg', 12_800, 400, 1_200, 'Gudang Pakan B', 90],
            ['FD-LMR01', 'Limir Beras', 'concentrate', 'kg', 9_800, 500, 1_500, 'Gudang Pakan B', 120],
            ['FD-DDK01', 'Dedak Jagung', 'concentrate', 'kg', 6_200, 400, 1_200, 'Gudang Pakan B', 120],
            ['FD-JNG01', 'Jagung Pipil Kering', 'concentrate', 'kg', 7_400, 700, 2_000, 'Gudang Pakan B', 180],
            ['FD-KDL01', 'Bungkil Kelapa Sawit', 'concentrate', 'kg', 8_900, 600, 1_800, 'Gudang Pakan C', 150],
            ['FD-KED01', 'Bungkil Kedelai', 'concentrate', 'kg', 16_500, 350, 1_000, 'Gudang Pakan C', 120],

            // Suplemen
            ['FD-MIN01', 'Mineral Blok Sapi Perah', 'supplement', 'bag', 185_000, 20, 50, 'Gudang Pakan C', 720],
            ['FD-GRM01', 'Garam Dapur', 'supplement', 'kg', 4_500, 40, 100, 'Gudang Pakan C', 720],
            ['FD-VIT01', 'Vitamin ADE 1L', 'supplement', 'liter', 320_000, 5, 12, 'Kantor', 365],
        ];

        $items = [];

        foreach ($definitions as [$code, $name, $category, $unit, $price, $reorder, $reorderQty, $location, $shelfLife]) {
            $items[$code] = FeedItem::create([
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'price_per_unit' => $price,
                'reorder_point' => $reorder,
                'reorder_quantity' => $reorderQty,
                'storage_location' => $location,
                'shelf_life_days' => $shelfLife,
                'is_active' => true,
            ]);
        }

        return $items;
    }

    /** @return array<int, Supplier> */
    private function suppliers(): array
    {
        return [
            Supplier::create([
                'code' => 'SUP-001', 'name' => 'CV Sumber Pakan Sejahtera', 'contact_person' => 'Pak Hadi',
                'phone' => '081211112222', 'email' => 'sales@sumberpakan.co.id',
                'address' => 'Jl. Raya Gianyar 45', 'city' => 'Gresik',
                'tax_number' => '01.234.567.8-901.000', 'payment_terms_days' => 14, 'category' => 'feed', 'is_active' => true,
            ]),
            Supplier::create([
                'code' => 'SUP-002', 'name' => 'UD Tani Makmur', 'contact_person' => 'Bu Ratna',
                'phone' => '081233334444', 'email' => 'order@tanimakmur.co.id',
                'address' => 'Jl. Ijen 12', 'city' => 'Jember',
                'tax_number' => '02.345.678.9-012.000', 'payment_terms_days' => 30, 'category' => 'feed', 'is_active' => true,
            ]),
            Supplier::create([
                'code' => 'SUP-003', 'name' => 'CV Sinar Mandiri Perlengkapan', 'contact_person' => 'Pak Joko',
                'phone' => '081255556666', 'email' => 'admin@sinarindep.id',
                'address' => 'Jl. Raya Gedangan 7', 'city' => 'Sidoarjo',
                'tax_number' => '03.456.789.0-123.000', 'payment_terms_days' => 0, 'category' => 'equipment', 'is_active' => true,
            ]),
        ];
    }

    /**
     * Scenario 1: ordered 5,000 kg, received 4,000 kg, 1,000 kg outstanding.
     */
    private function scenarioPartialReceipt(InventoryService $inventory, array $feedItems, array $suppliers, User $manager, User $owner): void
    {
        $order = PurchaseOrder::create([
            'po_number' => 'PO-'.now()->subDays(20)->format('ym').'-0001',
            'supplier_id' => $suppliers[0]->id,
            'order_date' => now()->subDays(20)->toDateString(),
            'expected_date' => now()->subDays(13)->toDateString(),
            'status' => PurchaseOrderStatus::Approved,
            'notes' => 'Pesanan rutin pakan konsentrat dan silase untuk dua bulan ke depan.',
            'created_by' => $manager->id,
            'approved_by' => $owner->id,
            'approved_at' => now()->subDays(20),
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'feed_item_id' => $feedItems['FD-KNS01']->id,
            'quantity' => 5_000,
            'unit' => 'kg',
            'unit_price' => 10_000,
            'batch_hint' => 'KNS-2409',
        ]);

        $order->recalculateTotals();

        // Supplier only delivered 4,000 kg of the 5,000 kg ordered.
        $receipt = GoodsReceipt::create([
            'receipt_number' => 'GR-'.now()->subDays(13)->format('ym').'-0001',
            'purchase_order_id' => $order->id,
            'supplier_id' => $suppliers[0]->id,
            'received_date' => now()->subDays(13)->toDateString(),
            'status' => GoodsReceiptStatus::Draft,
            'delivery_note_reference' => 'DO-2409-118',
            'quality_notes' => 'Kadar moisture 12%, tidak berjamur.',
            'received_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        GoodsReceiptItem::create([
            'goods_receipt_id' => $receipt->id,
            'purchase_order_item_id' => $item->id,
            'feed_item_id' => $feedItems['FD-KNS01']->id,
            'quantity_received' => 4_000,
            'unit' => 'kg',
            'unit_price' => 10_000,
            'batch_number' => 'KNS-2409-A',
            'expiry_date' => now()->addDays(80)->toDateString(),
            'quality_grade' => 'A',
        ]);

        $receipt->recalculateTotal();

        $inventory->postGoodsReceipt($receipt, $manager);

        $order->refresh();

        $this->command?->info("PO {$order->po_number}: dipesan 5.000 kg, diterima 4.000 kg, sisa ".number_format($order->totalOutstanding(), 0).' kg.');
    }

    private function openingBalances(InventoryService $inventory, array $feedItems, User $owner): void
    {
        $balances = [
            // Opening stock for the whole catalogue, not a handful of lines.
            // Leaving the newly added feeds at zero made the list read as an
            // empty warehouse with half the catalogue below its reorder point.
            'FD-SLS01' => [12_000, 'Saldo awal persediaan silase jagung'],
            'FD-SLS02' => [5_400, 'Saldo awal persediaan silase sorgum'],
            'FD-HPT01' => [6_000, 'Saldo awal persediaan hay padi'],
            'FD-JTG01' => [7_500, 'Saldo awal persediaan jerami padi'],
            'FD-RMP02' => [2_100, 'Saldo awal rumput lapangan'],
            'FD-KNS01' => [2_600, 'Saldo awal konsentrat 8-16'],
            'FD-KNS02' => [380, 'Saldo awal konsentrat awal laktasi'],
            'FD-LMR01' => [4_000, 'Saldo awal persediaan limir beras'],
            'FD-DDK01' => [2_500, 'Saldo awal persediaan dedak jagung'],
            'FD-JNG01' => [3_300, 'Saldo awal persediaan jagung pipil'],
            'FD-KDL01' => [2_400, 'Saldo awal persediaan bungkil kelapa sawit'],
            'FD-KED01' => [900, 'Saldo awal persediaan bungkil kedelai'],
            'FD-MIN01' => [40, 'Saldo awal persediaan mineral blok'],
            'FD-GRM01' => [95, 'Saldo awal persediaan garam dapur'],
            'FD-VIT01' => [8, 'Saldo awal persediaan vitamin ADE'],
        ];

        foreach ($balances as $code => [$quantity, $reason]) {
            $inventory->adjust(
                feedItem: $feedItems[$code],
                type: MovementType::Opening,
                quantity: (float) $quantity,
                reason: $reason,
                reference: 'ADJ-OPENING',
                movementDate: now()->subDays(90)->toDateString(),
                user: $owner,
            );
        }
    }

    /**
     * Scenario 2: consumption reduces stock and is traceable to batch.
     */
    private function consumptionHistory(FeedConsumptionService $service, $farm, array $feedItems, User $manager, User $worker): void
    {
        $barns = Barn::orderBy('code')->get();

        $plan = [
            ['barn' => 'KAND-A', 'days' => 12, 'animal_count' => 14, 'lines' => [
                ['code' => 'FD-SLS01', 'quantity' => 35.0],
                ['code' => 'FD-KNS01', 'quantity' => 28.0],
                ['code' => 'FD-MIN01', 'quantity' => 0.1],
            ]],
            ['barn' => 'KAND-B', 'days' => 12, 'animal_count' => 10, 'lines' => [
                ['code' => 'FD-SLS01', 'quantity' => 30.0],
                ['code' => 'FD-KNS01', 'quantity' => 22.0],
            ]],
            ['barn' => 'KAND-C', 'days' => 6, 'animal_count' => 5, 'lines' => [
                ['code' => 'FD-HPT01', 'quantity' => 16.0],
                ['code' => 'FD-LMR01', 'quantity' => 3.0],
            ]],
        ];

        $date = now()->subDays(11);

        foreach ($plan as $entry) {
            $barn = $barns->firstWhere('code', $entry['barn']);

            for ($i = 0; $i < $entry['days']; $i++) {
                $items = [];

                foreach ($entry['lines'] as $line) {
                    $items[] = [
                        'feed_item_id' => $feedItems[$line['code']]->id,
                        'quantity' => $line['quantity'],
                    ];
                }

                $service->record([
                    'farm_id' => $farm->id,
                    'barn_id' => $barn->id,
                    'consumption_date' => $date->copy()->toDateString(),
                    'animal_count' => $entry['animal_count'],
                    'notes' => $i % 5 === 0 ? 'Pemberian pagi dan sore.' : null,
                    'items' => $items,
                ], $worker);

                $date->addDay();
            }
        }

        // A single group consumption of 800 kg silage, drawn from the delivered batch.
        $barnA = $barns->firstWhere('code', 'KAND-A');

        $batch = InventoryBatch::query()
            ->where('batch_code', 'KNS-2409-A')
            ->first();

        $service->record([
            'farm_id' => $farm->id,
            'barn_id' => $barnA->id,
            'consumption_date' => now()->subDays(6)->toDateString(),
            'animal_count' => 14,
            'notes' => 'Pemakaian skenario: 800 kg diambil dari batch penerimaan KNS-2409-A.',
            'items' => [[
                'feed_item_id' => $batch->feed_item_id,
                'inventory_batch_id' => $batch->id,
                'quantity' => 800,
            ]],
        ], $worker);
    }
}
