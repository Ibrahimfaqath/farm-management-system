<?php

namespace Tests\Feature;

use App\Enums\BatchStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\FeedConsumption;
use App\Models\FeedItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\FeedConsumptionService;
use App\Services\InsufficientStockException;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function purchaseOrder(float $quantity = 5_000, float $price = 8_000): PurchaseOrder
    {
        $supplier = Supplier::factory()->create();
        $feedItem = FeedItem::factory()->create(['price_per_unit' => $price, 'reorder_point' => 0]);

        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => PurchaseOrderStatus::Approved,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'feed_item_id' => $feedItem->id,
            'quantity' => $quantity,
            'unit' => 'kg',
            'unit_price' => $price,
        ]);

        $order->recalculateTotals();

        return $order->fresh();
    }

    /**
     * @return array{0: GoodsReceipt, 1: GoodsReceiptItem}
     */
    private function receive(PurchaseOrder $order, float $quantity, string $status = GoodsReceiptStatus::Draft->value): array
    {
        $poItem = $order->items()->first();
        $feedItem = FeedItem::find($poItem->feed_item_id);

        $receipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'status' => $status,
        ]);

        $item = GoodsReceiptItem::create([
            'goods_receipt_id' => $receipt->id,
            'purchase_order_item_id' => $poItem->id,
            'feed_item_id' => $feedItem->id,
            'quantity_received' => $quantity,
            'unit' => 'kg',
            'unit_price' => (float) $poItem->unit_price,
            'batch_number' => 'CS-001',
        ]);

        $receipt->recalculateTotal();

        return [$receipt->fresh(), $item];
    }

    public function test_purchase_order_does_not_increase_stock(): void
    {
        $inventory = app(InventoryService::class);
        $order = $this->purchaseOrder();
        $feedItem = FeedItem::find($order->items()->first()->feed_item_id);

        $this->assertSame(0.0, $inventory->stockOf($feedItem));
        $this->assertSame(0, StockMovement::count());
    }

    public function test_draft_goods_receipt_does_not_increase_stock(): void
    {
        $inventory = app(InventoryService::class);
        $order = $this->purchaseOrder();
        [$receipt] = $this->receive($order, 4_000, GoodsReceiptStatus::Draft->value);

        $feedItem = FeedItem::find($order->items()->first()->feed_item_id);

        $this->assertSame(0.0, $inventory->stockOf($feedItem));
        $this->assertSame(0, StockMovement::count());
    }

    public function test_posted_goods_receipt_increases_stock_and_creates_batch(): void
    {
        $inventory = app(InventoryService::class);
        $order = $this->purchaseOrder();
        [$receipt] = $this->receive($order, 4_000);

        $inventory->postGoodsReceipt($receipt);

        $feedItem = FeedItem::find($order->items()->first()->feed_item_id);

        $this->assertSame(4000.0, $inventory->stockOf($feedItem));
        $this->assertSame(GoodsReceiptStatus::Posted, $receipt->fresh()->status);

        $batch = InventoryBatch::where('feed_item_id', $feedItem->id)->firstOrFail();
        $this->assertSame('CS-001', $batch->batch_code);
        $this->assertSame(4000.0, (float) $batch->quantity_remaining);
        $this->assertSame(BatchStatus::Active, $batch->status);

        $this->assertDatabaseHas('stock_movements', [
            'feed_item_id' => $feedItem->id,
            'type' => MovementType::Receipt->value,
            'quantity' => 4000,
        ]);
    }

    public function test_partial_receipt_preserves_outstanding_quantity(): void
    {
        $inventory = app(InventoryService::class);
        $order = $this->purchaseOrder(5_000);
        [$receipt] = $this->receive($order, 4_000);

        $inventory->postGoodsReceipt($receipt);

        $order->refresh();

        $this->assertSame(4000.0, $order->totalReceived());
        $this->assertSame(1000.0, $order->totalOutstanding());
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $order->status);
        $this->assertSame(1000.0, $order->items()->first()->outstandingQuantity());
    }

    public function test_fully_received_order_is_marked_received(): void
    {
        $inventory = app(InventoryService::class);
        $order = $this->purchaseOrder(5_000);
        [$receipt] = $this->receive($order, 5_000);

        $inventory->postGoodsReceipt($receipt);

        $this->assertSame(PurchaseOrderStatus::Received, $order->fresh()->status);
        $this->assertSame(0.0, $order->totalOutstanding());
    }

    public function test_feed_consumption_form_shows_automatic_fifo_without_a_batch_dropdown(): void
    {
        $owner = $this->owner();

        $html = $this->actingAs($owner)
            ->get(route('feed-consumptions.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('Automatic FIFO'), $html);
        $this->assertStringNotContainsString('name="items[0][inventory_batch_id]"', $html);
        $this->assertStringNotContainsString('data-line-batch', $html);
    }

    public function test_consumption_decreases_stock_and_is_traceable(): void
    {
        $inventory = app(InventoryService::class);
        $consumptionService = app(FeedConsumptionService::class);
        $order = $this->purchaseOrder(5_000);

        [$receipt] = $this->receive($order, 5_000);
        $inventory->postGoodsReceipt($receipt);

        $feedItem = FeedItem::find($order->items()->first()->feed_item_id);
        $farm = Farm::first() ?? Farm::factory()->create();

        $result = $consumptionService->record([
            'farm_id' => $farm->id,
            'consumption_date' => now()->toDateString(),
            'animal_count' => 10,
            'items' => [
                ['feed_item_id' => $feedItem->id, 'quantity' => 800],
            ],
        ]);

        $this->assertSame(4200.0, $inventory->stockOf($feedItem));
        $this->assertSame(800.0, $result['total_quantity']);

        $movement = StockMovement::where('type', MovementType::Consumption->value)->firstOrFail();
        $this->assertNotNull($movement->inventory_batch_id);
        $this->assertSame($result['consumption']->id, $movement->source_id);

        $batch = InventoryBatch::where('feed_item_id', $feedItem->id)->firstOrFail();
        $this->assertSame(4200.0, (float) $batch->quantity_remaining);
    }

    public function test_over_consumption_is_blocked_without_override(): void
    {
        $inventory = app(InventoryService::class);
        $consumptionService = app(FeedConsumptionService::class);
        $order = $this->purchaseOrder(1_000);

        [$receipt] = $this->receive($order, 1_000);
        $inventory->postGoodsReceipt($receipt);

        $feedItem = FeedItem::find($order->items()->first()->feed_item_id);
        $farm = Farm::first() ?? Farm::factory()->create();

        $this->expectException(InsufficientStockException::class);

        try {
            $consumptionService->record([
                'farm_id' => $farm->id,
                'consumption_date' => now()->toDateString(),
                'animal_count' => 5,
                'items' => [
                    ['feed_item_id' => $feedItem->id, 'quantity' => 1_500],
                ],
            ]);
        } finally {
            $this->assertSame(1_000.0, $inventory->stockOf($feedItem));
            $this->assertSame(0, FeedConsumption::count());
        }
    }

    public function test_over_consumption_is_allowed_with_explicit_override(): void
    {
        $inventory = app(InventoryService::class);
        $consumptionService = app(FeedConsumptionService::class);
        $order = $this->purchaseOrder(1_000);

        [$receipt] = $this->receive($order, 1_000);
        $inventory->postGoodsReceipt($receipt);

        $feedItem = FeedItem::find($order->items()->first()->feed_item_id);
        $farm = Farm::first() ?? Farm::factory()->create();
        $user = $this->owner();

        $result = $consumptionService->record([
            'farm_id' => $farm->id,
            'consumption_date' => now()->toDateString(),
            'animal_count' => 5,
            'allow_negative_stock' => true,
            'override_reason' => 'Hasil timbangan lebih tinggi, akan dikoreksi',
            'items' => [
                ['feed_item_id' => $feedItem->id, 'quantity' => 1_500],
            ],
        ], $user);

        $this->assertSame(-500.0, $inventory->stockOf($feedItem));
        $this->assertNotNull($result['consumption']->override_by);

        $this->assertDatabaseHas('stock_movements', [
            'type' => MovementType::AdjustmentOut->value,
            'quantity' => 500,
        ]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'inventory.negative_stock_override']);
    }

    public function test_manual_adjustment_requires_reason_and_moves_stock(): void
    {
        $inventory = app(InventoryService::class);
        $feedItem = FeedItem::factory()->create(['price_per_unit' => 5_000]);

        $result = $inventory->adjust(
            feedItem: $feedItem,
            type: MovementType::Opening,
            quantity: 1_200,
            reason: 'Saldo awal pembukuan tahun baru',
            reference: 'ADJ-1',
            user: $this->owner(),
        );

        $this->assertSame(0.0, $result['stock_before']);
        $this->assertSame(1_200.0, $result['stock_after']);
        $this->assertSame(1_200.0, $inventory->stockOf($feedItem));

        $this->assertDatabaseHas('activity_logs', ['action' => 'inventory.adjusted']);
    }

    public function test_low_stock_detection_uses_reorder_point(): void
    {
        $inventory = app(InventoryService::class);
        $feedItem = FeedItem::factory()->create(['reorder_point' => 1_000]);
        $other = FeedItem::factory()->create(['reorder_point' => 100]);

        $inventory->adjust($feedItem, MovementType::Opening, 900, 'Saldo awal', reference: 'ADJ-2');
        $inventory->adjust($other, MovementType::Opening, 5_000, 'Saldo awal', reference: 'ADJ-3');

        $low = $inventory->lowStockItems();

        $this->assertCount(1, $low);
        $this->assertSame($feedItem->id, $low->first()->id);
    }

    public function test_fifo_allocates_from_oldest_batch_first(): void
    {
        $inventory = app(InventoryService::class);
        $feedItem = FeedItem::factory()->create(['price_per_unit' => 7_000]);

        $older = InventoryBatch::create([
            'feed_item_id' => $feedItem->id,
            'batch_code' => 'B-OLD',
            'unit' => 'kg',
            'received_date' => now()->subDays(60)->toDateString(),
            'original_quantity' => 1_000,
            'quantity_remaining' => 1_000,
            'status' => BatchStatus::Active,
        ]);

        $newer = InventoryBatch::create([
            'feed_item_id' => $feedItem->id,
            'batch_code' => 'B-NEW',
            'unit' => 'kg',
            'received_date' => now()->subDay()->toDateString(),
            'original_quantity' => 2_000,
            'quantity_remaining' => 2_000,
            'status' => BatchStatus::Active,
        ]);

        $consumptionService = app(FeedConsumptionService::class);
        $farm = Farm::factory()->create();

        $consumptionService->record([
            'farm_id' => $farm->id,
            'consumption_date' => now()->toDateString(),
            'items' => [['feed_item_id' => $feedItem->id, 'quantity' => 1_500]],
        ]);

        $this->assertSame(0.0, (float) $older->fresh()->quantity_remaining);
        $this->assertSame(BatchStatus::Depleted, $older->fresh()->status);
        $this->assertSame(1_500.0, (float) $newer->fresh()->quantity_remaining);
    }

    public function test_consumption_allocations_do_not_double_deduct_stock(): void
    {
        $inventory = app(InventoryService::class);
        $consumptionService = app(FeedConsumptionService::class);
        $feedItem = FeedItem::factory()->create(['price_per_unit' => 4_000]);

        $inventory->adjust($feedItem, MovementType::Opening, 1_000, 'Saldo awal', reference: 'ADJ-4');

        $farm = Farm::factory()->create();
        $animals = Animal::factory()->count(2)->create(['farm_id' => $farm->id]);

        $result = $consumptionService->record([
            'farm_id' => $farm->id,
            'consumption_date' => now()->toDateString(),
            'animal_count' => 2,
            'items' => [['feed_item_id' => $feedItem->id, 'quantity' => 200]],
        ]);

        $consumptionService->allocate($result['consumption'], [
            ['animal_id' => $animals[0]->id, 'quantity' => 100, 'feed_consumption_item_id' => $result['consumption']->items()->first()->id],
            ['animal_id' => $animals[1]->id, 'quantity' => 100, 'feed_consumption_item_id' => $result['consumption']->items()->first()->id],
        ]);

        $this->assertSame(800.0, $inventory->stockOf($feedItem));
        $this->assertSame(1, StockMovement::where('type', MovementType::Consumption->value)->count());
        $this->assertSame(2, $result['consumption']->allocations()->count());
    }

    public function test_reversing_a_consumption_returns_stock(): void
    {
        $inventory = app(InventoryService::class);
        $consumptionService = app(FeedConsumptionService::class);
        $feedItem = FeedItem::factory()->create(['price_per_unit' => 4_000]);

        $inventory->adjust($feedItem, MovementType::Opening, 1_000, 'Saldo awal', reference: 'ADJ-5');

        $farm = Farm::factory()->create();
        $result = $consumptionService->record([
            'farm_id' => $farm->id,
            'consumption_date' => now()->toDateString(),
            'items' => [['feed_item_id' => $feedItem->id, 'quantity' => 300]],
        ]);

        $this->assertSame(700.0, $inventory->stockOf($feedItem));

        $consumptionService->reverse($result['consumption'], 'Input ganda oleh petugas', $this->owner());

        $this->assertSame(1_000.0, $inventory->stockOf($feedItem));
    }
}
