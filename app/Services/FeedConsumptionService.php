<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Models\FeedConsumption;
use App\Models\FeedConsumptionAllocation;
use App\Models\FeedConsumptionItem;
use App\Models\FeedItem;
use App\Models\InventoryBatch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Records feed consumption. Stock is deducted exactly once per consumption
 * line; individual-animal allocations are operational detail only.
 */
class FeedConsumptionService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{
     *     farm_id: int,
     *     barn_id?: int|null,
     *     consumption_date: string,
     *     animal_count?: int,
     *     notes?: string|null,
     *     allow_negative_stock?: bool,
     *     override_reason?: string|null,
     *     items: list<array{feed_item_id: int, inventory_batch_id?: int|null, quantity: float}>
     * }  $data
     * @return array{consumption: FeedConsumption, lines: list<array<string, mixed>>, total_quantity: float, total_amount: float}
     */
    public function record(array $data, ?User $user = null): array
    {
        if (($data['items'] ?? []) === []) {
            throw new \InvalidArgumentException('Pemakaian Minimal satu item.');
        }

        return DB::transaction(function () use ($data, $user): array {
            $consumption = FeedConsumption::create([
                'consumption_number' => $this->nextNumber($data['consumption_date']),
                'farm_id' => $data['farm_id'],
                'barn_id' => $data['barn_id'] ?? null,
                'consumption_date' => $data['consumption_date'],
                'animal_count' => (int) ($data['animal_count'] ?? 0),
                'allow_negative_stock' => (bool) ($data['allow_negative_stock'] ?? false),
                'override_reason' => $data['override_reason'] ?? null,
                'override_by' => ($data['allow_negative_stock'] ?? false) ? $user?->getKey() : null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->getKey(),
            ]);

            $lines = [];

            foreach ($data['items'] as $line) {
                /** @var FeedItem $feedItem */
                $feedItem = FeedItem::query()->findOrFail($line['feed_item_id']);

                $item = FeedConsumptionItem::create([
                    'feed_consumption_id' => $consumption->getKey(),
                    'feed_item_id' => $feedItem->getKey(),
                    'inventory_batch_id' => $line['inventory_batch_id'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit' => $feedItem->unit->value,
                    'unit_cost' => (float) $feedItem->price_per_unit,
                ]);

                $impact = $this->inventory->deductForConsumption($consumption, [
                    'feed_consumption_id' => $consumption->getKey(),
                    'feed_item_id' => $feedItem->getKey(),
                    'inventory_batch_id' => $line['inventory_batch_id'] ?? null,
                    'quantity' => (float) $line['quantity'],
                    'unit' => $feedItem->unit->value,
                ], $user);

                $item->update([
                    'stock_before' => $impact['stock_before'],
                    'stock_after' => $impact['stock_after'],
                ]);

                $lines[] = [
                    'item' => $item->fresh(),
                    'batch_codes' => $impact['batch_codes'],
                    'stock_before' => $impact['stock_before'],
                    'stock_after' => $impact['stock_after'],
                ];
            }

            $totalQuantity = (float) $consumption->items()->sum('quantity');
            $totalAmount = (float) $consumption->items()->sum('line_total');

            $consumption->update([
                'total_quantity' => $totalQuantity,
                'total_amount' => $totalAmount,
                'average_per_animal' => $consumption->animal_count > 0
                    ? round($totalQuantity / $consumption->animal_count, 4)
                    : 0,
            ]);

            $this->audit->log(
                'feed.consumed',
                $consumption,
                "Pemakaian pakan {$consumption->consumption_number} sebesar ".round($totalQuantity, 2).' unit',
                $consumption->consumption_number,
                ['total_amount' => $totalAmount, 'lines' => count($lines)],
            );

            return [
                'consumption' => $consumption->fresh(),
                'lines' => $lines,
                'total_quantity' => $totalQuantity,
                'total_amount' => $totalAmount,
            ];
        });
    }

    /**
     * Attribute part of a consumption line to individual animals.
     * This never touches stock: the parent line already did the deduction.
     *
     * @param  list<array{animal_id: int, quantity: float, feed_consumption_item_id?: int|null}>  $allocations
     */
    public function allocate(FeedConsumption $consumption, array $allocations): void
    {
        foreach ($allocations as $allocation) {
            FeedConsumptionAllocation::create([
                'feed_consumption_id' => $consumption->getKey(),
                'feed_consumption_item_id' => $allocation['feed_consumption_item_id'] ?? null,
                'animal_id' => $allocation['animal_id'],
                'quantity' => $allocation['quantity'],
            ]);
        }
    }

    private function nextNumber(string $date): string
    {
        $day = Carbon::parse($date)->format('Ymd');
        $prefix = 'FC-'.$day.'-';

        $latest = FeedConsumption::query()
            ->where('consumption_number', 'like', $prefix.'%')
            ->orderByDesc('consumption_number')
            ->value('consumption_number');

        $sequence = $latest ? ((int) Str::afterLast($latest, '-')) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Reverse a consumption: post compensating inbound movements so the ledger
     * stays explainable. The original document is kept for the audit trail.
     *
     * @return array{quantity: float, amount: float}
     */
    public function reverse(FeedConsumption $consumption, string $reason, ?User $user = null): array
    {
        return DB::transaction(function () use ($consumption, $reason, $user): array {
            $quantity = 0.0;
            $amount = 0.0;

            foreach ($consumption->items as $item) {
                $feedItem = $item->feedItem;

                $batch = $item->batch;

                if ($batch) {
                    $batch->update([
                        'quantity_remaining' => (float) $batch->quantity_remaining + (float) $item->quantity,
                        'status' => BatchStatus::Active,
                    ]);
                }

                $this->inventory->recordMovement(
                    feedItem: $feedItem,
                    type: MovementType::AdjustmentIn,
                    quantity: (float) $item->quantity,
                    movementDate: now()->toDateString(),
                    reference: $consumption->consumption_number.' (koreksi)',
                    source: $consumption,
                    batch: $batch,
                    unitCost: (float) $item->unit_cost,
                    user: $user,
                    reason: $reason,
                );

                $quantity += (float) $item->quantity;
                $amount += (float) $item->line_total;
            }

            $consumption->update(['notes' => trim(($consumption->notes ?? '')."\n[DIKOREKSI] $reason")]);

            $this->audit->log(
                'feed.consumption_reversed',
                $consumption,
                "Pemakaian {$consumption->consumption_number} dikoreksi: $reason",
                $consumption->consumption_number,
            );

            return ['quantity' => $quantity, 'amount' => $amount];
        });
    }

    /**
     * Average daily consumption of a feed item over a window, used for the
     * feed report and reorder suggestions.
     */
    public function averageDailyConsumption(FeedItem $feedItem, int $days = 30, ?int $farmId = null): float
    {
        $total = (float) FeedConsumptionItem::query()
            ->where('feed_item_id', $feedItem->getKey())
            ->whereHas('feedConsumption', fn ($q) => $q
                ->whereDate('consumption_date', '>=', now()->subDays($days)->toDateString())
                ->when($farmId, fn ($q) => $q->where('farm_id', $farmId)))
            ->sum('quantity');

        return round($total / max(1, $days), 4);
    }

    /**
     * @return list<InventoryBatch>
     */
    public function batchesFor(FeedItem $feedItem): array
    {
        return $this->inventory->usableBatches($feedItem)->all();
    }
}
