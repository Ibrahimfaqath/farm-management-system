<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockDirection;
use App\Models\FeedConsumption;
use App\Models\FeedItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryBatch;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Inventory is derived from stock movements. Nothing writes a stock number
 * directly, so every change is traceable to a document.
 */
class InventoryService
{
    public function __construct(private readonly AuditLogger $audit) {}

    // ------------------------------------------------------------------
    // Queries
    // ------------------------------------------------------------------

    /**
     * Current stock for a feed item, derived from posted movements.
     */
    public function stockOf(FeedItem $feedItem): float
    {
        return round((float) $feedItem->stockMovements()->signed()->value('signed_quantity'), 4);
    }

    /**
     * Current stock per feed item keyed by feed item id.
     *
     * @return array<int, float>
     */
    public function stockMap(): array
    {
        return StockMovement::query()
            ->select('feed_item_id')
            ->signed('stock')
            ->groupBy('feed_item_id')
            ->pluck('stock', 'feed_item_id')
            ->map(fn ($value) => round((float) $value, 4))
            ->all();
    }

    /**
     * Feed items whose stock is at or below the reorder point.
     *
     * @return Collection<int, FeedItem>
     */
    public function lowStockItems(): Collection
    {
        $stock = $this->stockMap();

        return FeedItem::query()
            ->where('is_active', true)
            ->get()
            ->map(function (FeedItem $item) use ($stock) {
                $item->setAttribute('current_stock', $stock[$item->getKey()] ?? 0.0);

                return $item;
            })
            ->filter(fn (FeedItem $item) => (float) $item->reorder_point > 0
                && (float) $item->current_stock <= (float) $item->reorder_point)
            ->sortBy('current_stock')
            ->values();
    }

    /**
     * Batches of a feed item that can still be consumed, oldest first.
     *
     * @return Collection<int, InventoryBatch>
     */
    public function usableBatches(FeedItem $feedItem, bool $onlyExpiring = false): Collection
    {
        return InventoryBatch::query()
            ->where('feed_item_id', $feedItem->getKey())
            ->where('status', BatchStatus::Active->value)
            ->where('quantity_remaining', '>', 0)
            ->when($onlyExpiring, fn ($q) => $q->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', now()->toDateString()))
            ->orderByRaw('expiry_date IS NULL, expiry_date asc')
            ->orderBy('received_date')
            ->get();
    }

    /**
     * Usable stock of a feed item (excluding quarantined or expired batches).
     */
    public function usableStock(FeedItem $feedItem): float
    {
        return (float) InventoryBatch::query()
            ->where('feed_item_id', $feedItem->getKey())
            ->where('status', BatchStatus::Active->value)
            ->where('quantity_remaining', '>', 0)
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString()))
            ->sum('quantity_remaining');
    }

    /**
     * Check whether a consumption is possible and describe the outcome.
     *
     * @return array{available: float, requested: float, shortfall: float, allowed: bool}
     */
    public function checkConsumption(FeedItem $feedItem, float $quantity): array
    {
        $available = $this->usableStock($feedItem);
        $shortfall = max(0, round($quantity - $available, 4));

        return [
            'available' => $available,
            'requested' => $quantity,
            'shortfall' => $shortfall,
            'allowed' => $shortfall <= 0,
        ];
    }

    // ------------------------------------------------------------------
    // Movements
    // ------------------------------------------------------------------

    /**
     * Post a posted goods receipt: create batches, inbound movements and refresh
     * the purchase order status.
     *
     * @return array<string, mixed>
     */
    public function postGoodsReceipt(GoodsReceipt $receipt, ?User $user = null): array
    {
        if ($receipt->status === GoodsReceiptStatus::Posted) {
            return ['already_posted' => true];
        }

        return DB::transaction(function () use ($receipt, $user): array {
            $receipt->items()->with('feedItem')->get()->each(
                fn (GoodsReceiptItem $item) => $this->receiveItem($receipt, $item, $user)
            );

            $receipt->recalculateTotal();
            $receipt->update([
                'status' => GoodsReceiptStatus::Posted,
                'posted_at' => now(),
            ]);

            $poSummary = null;

            if ($receipt->purchase_order_id) {
                $receipt->purchaseOrder?->recalculateTotals();
                $receipt->purchaseOrder?->refresh();
                $poSummary = $this->refreshPurchaseOrderStatus($receipt->purchaseOrder);
            }

            $this->audit->log(
                'inventory.receipt_posted',
                $receipt,
                "Penerimaan {$receipt->receipt_number} dikonfirmasi, stok bertambah",
                $receipt->receipt_number,
                ['total' => (float) $receipt->fresh()->total_amount],
            );

            return [
                'receipt' => $receipt->fresh(),
                'purchase_order' => $poSummary,
            ];
        });
    }

    private function receiveItem(GoodsReceipt $receipt, GoodsReceiptItem $item, ?User $user): InventoryBatch
    {
        $feedItem = $item->feedItem;
        $batchCode = $item->batch_number ?: $this->generateBatchCode($feedItem, $receipt);

        $batch = InventoryBatch::create([
            'feed_item_id' => $feedItem->getKey(),
            'goods_receipt_item_id' => $item->getKey(),
            'batch_code' => $batchCode,
            'unit' => $item->unit->value,
            'received_date' => $receipt->received_date,
            'original_quantity' => $item->quantity_received,
            'quantity_remaining' => $item->quantity_received,
            'expiry_date' => $item->expiry_date,
            'storage_location' => $feedItem->storage_location,
            'quality_grade' => $item->quality_grade,
            'status' => BatchStatus::Active,
        ]);

        $this->recordMovement(
            feedItem: $feedItem,
            type: MovementType::Receipt,
            quantity: (float) $item->quantity_received,
            movementDate: $receipt->received_date,
            reference: $receipt->receipt_number,
            source: $receipt,
            batch: $batch,
            unitCost: (float) $item->unit_price,
            user: $user,
        );

        return $batch;
    }

    /**
     * Deduct stock for a consumption line, allocating from batches FIFO.
     *
     * @param  array{feed_consumption_id: int, feed_item_id: int, inventory_batch_id?: int|null, quantity: float, unit: string}  $line
     * @return array{batch_codes: list<string>, stock_before: float, stock_after: float}
     */
    public function deductForConsumption(FeedConsumption $consumption, array $line, ?User $user = null): array
    {
        /** @var FeedItem $feedItem */
        $feedItem = FeedItem::query()->findOrFail($line['feed_item_id']);
        $quantity = (float) $line['quantity'];

        return DB::transaction(function () use ($feedItem, $consumption, $line, $quantity, $user): array {
            $stockBefore = $this->stockOf($feedItem);
            $batchCodes = [];
            $remaining = round($quantity, 4);

            $batches = $this->resolveBatches($feedItem, $line['inventory_batch_id'] ?? null);

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, (float) $batch->quantity_remaining);

                if ($take <= 0) {
                    continue;
                }

                $this->recordMovement(
                    feedItem: $feedItem,
                    type: MovementType::Consumption,
                    quantity: $take,
                    movementDate: $consumption->consumption_date,
                    reference: $consumption->consumption_number,
                    source: $consumption,
                    batch: $batch,
                    unitCost: $this->batchUnitCost($batch, $feedItem),
                    user: $user,
                );

                $batch->decrement('quantity_remaining', $take);

                if ((float) $batch->fresh()->quantity_remaining <= 0) {
                    $batch->update(['status' => BatchStatus::Depleted]);
                }

                $batchCodes[] = $batch->batch_code;
                $remaining = round($remaining - $take, 4);
            }

            $shortfall = $remaining;

            if ($shortfall > 0 && ! $consumption->allow_negative_stock) {
                throw new InsufficientStockException(
                    message: "Stok {$feedItem->name} tidak mencukupi.",
                    feedItem: $feedItem,
                    requested: $quantity,
                    available: $this->usableStock($feedItem),
                    shortfall: $shortfall,
                );
            }

            if ($shortfall > 0) {
                // Authorised override: an explicit negative correction is posted
                // so the ledger still explains the difference.
                $this->recordMovement(
                    feedItem: $feedItem,
                    type: MovementType::AdjustmentOut,
                    quantity: $shortfall,
                    movementDate: $consumption->consumption_date,
                    reference: $consumption->consumption_number,
                    source: $consumption,
                    batch: null,
                    unitCost: (float) $feedItem->price_per_unit,
                    user: $user,
                    reason: $consumption->override_reason ?? 'Override stok negatif',
                );

                $this->audit->log(
                    'inventory.negative_stock_override',
                    $consumption,
                    "Pemakaian melebihi stok: {$feedItem->name} kurang ".round($shortfall, 2).' '.$line['unit'],
                    $consumption->consumption_number,
                    ['feed_item_id' => $feedItem->getKey(), 'shortfall' => $shortfall],
                );
            }

            $stockAfter = $this->stockOf($feedItem);

            return [
                'batch_codes' => $batchCodes,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ];
        });
    }

    /**
     * Manual inventory adjustment with a mandatory reason.
     *
     * @return array{batch: InventoryBatch|null, stock_before: float, stock_after: float}
     */
    public function adjust(
        FeedItem $feedItem,
        MovementType $type,
        float $quantity,
        string $reason,
        InventoryBatch|FeedConsumption|GoodsReceipt|null $source = null,
        ?InventoryBatch $batch = null,
        ?string $reference = null,
        ?string $movementDate = null,
        ?User $user = null,
    ): array {
        if ($type->direction() === StockDirection::Out && $quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas penyesuaian keluar harus lebih dari nol.');
        }

        return DB::transaction(function () use ($feedItem, $type, $quantity, $reason, $source, $batch, $reference, $movementDate, $user): array {
            $stockBefore = $this->stockOf($feedItem);
            $date = $movementDate ?? now()->toDateString();

            // An inbound correction without a source document still needs a batch
            // so traceability and FIFO allocation stay intact.
            if (! $batch && $type->direction() === StockDirection::In) {
                $batch = $this->createImplicitBatch($feedItem, $quantity, $date, $reason);
            }

            $this->recordMovement(
                feedItem: $feedItem,
                type: $type,
                quantity: $quantity,
                movementDate: $date,
                reference: $reference,
                source: $source,
                batch: $batch,
                unitCost: (float) $feedItem->price_per_unit,
                user: $user,
                reason: $reason,
            );

            if ($batch) {
                $delta = $type->direction()->sign() * $quantity;
                $batch->increment('quantity_remaining', $delta);

                if ((float) $batch->fresh()->quantity_remaining <= 0) {
                    $batch->update(['status' => BatchStatus::Depleted]);
                }
            }

            $stockAfter = $this->stockOf($feedItem);

            $this->audit->log(
                'inventory.adjusted',
                $source ?? $feedItem,
                "Penyesuaian stok {$feedItem->name}: {$type->label()} ".round($quantity, 2).' — '.$reason,
                $reference,
                ['type' => $type->value, 'quantity' => $quantity, 'before' => $stockBefore, 'after' => $stockAfter],
            );

            return ['batch' => $batch?->fresh(), 'stock_before' => $stockBefore, 'stock_after' => $stockAfter];
        });
    }

    /**
     * Create one movement row and keep the batch counter in sync.
     */
    public function recordMovement(
        FeedItem $feedItem,
        MovementType $type,
        float $quantity,
        string|\DateTimeInterface $movementDate,
        ?string $reference = null,
        ?Model $source = null,
        ?InventoryBatch $batch = null,
        float $unitCost = 0,
        ?User $user = null,
        ?string $reason = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas pergerakan stok harus lebih dari nol.');
        }

        return StockMovement::create([
            'feed_item_id' => $feedItem->getKey(),
            'inventory_batch_id' => $batch?->getKey(),
            'type' => $type,
            'quantity' => $quantity,
            'unit' => $feedItem->unit->value,
            'movement_date' => $movementDate instanceof \DateTimeInterface
                ? $movementDate->format('Y-m-d')
                : $movementDate,
            'reference' => $reference,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'reason' => $reason,
            'unit_cost' => $unitCost,
            'notes' => $notes,
            'created_by' => $user?->getKey(),
        ]);
    }

    /**
     * @return Collection<int, InventoryBatch>
     */
    private function resolveBatches(FeedItem $feedItem, ?int $batchId): Collection
    {
        if ($batchId) {
            return InventoryBatch::query()
                ->where('feed_item_id', $feedItem->getKey())
                ->whereKey($batchId)
                ->lockForUpdate()
                ->get();
        }

        return InventoryBatch::query()
            ->where('feed_item_id', $feedItem->getKey())
            ->where('status', BatchStatus::Active->value)
            ->where('quantity_remaining', '>', 0)
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString()))
            ->orderByRaw('expiry_date IS NULL, expiry_date asc')
            ->orderBy('received_date')
            ->lockForUpdate()
            ->get();
    }

    private function batchUnitCost(InventoryBatch $batch, FeedItem $feedItem): float
    {
        return (float) ($batch->goodsReceiptItem?->unit_price ?? $feedItem->price_per_unit);
    }

    private function createImplicitBatch(FeedItem $feedItem, float $quantity, string $date, string $reason): InventoryBatch
    {
        $sequence = InventoryBatch::query()->where('feed_item_id', $feedItem->getKey())->count() + 1;

        return InventoryBatch::create([
            'feed_item_id' => $feedItem->getKey(),
            'batch_code' => 'ADJ-'.date('ymd', strtotime($date)).'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'unit' => $feedItem->unit->value,
            'received_date' => $date,
            'original_quantity' => $quantity,
            'quantity_remaining' => $quantity,
            'storage_location' => $feedItem->storage_location,
            'status' => BatchStatus::Active,
        ]);
    }

    private function generateBatchCode(FeedItem $feedItem, GoodsReceipt $receipt): string
    {
        $sequence = InventoryBatch::query()->where('feed_item_id', $feedItem->getKey())->count() + 1;

        return str($feedItem->code)->upper()->slug('-')->value()
            .'-'.$receipt->received_date->format('ymd')
            .'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{status: string, outstanding: float, received: float, ordered: float}
     */
    private function refreshPurchaseOrderStatus(PurchaseOrder $po): array
    {
        $ordered = (float) $po->items()->sum('quantity');
        $received = $po->totalReceived();
        $outstanding = max(0, round($ordered - $received, 4));

        $status = match (true) {
            $po->status->value === 'cancelled' => $po->status,
            $outstanding <= 0 => PurchaseOrderStatus::Received,
            $received > 0 => PurchaseOrderStatus::PartiallyReceived,
            $po->status->value === 'approved' => $po->status,
            default => PurchaseOrderStatus::Approved,
        };

        if ($status->value !== $po->status->value) {
            $po->update(['status' => $status]);
        }

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'ordered' => $ordered,
            'received' => $received,
            'outstanding' => $outstanding,
        ];
    }
}
