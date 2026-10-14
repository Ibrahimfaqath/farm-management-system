<?php

namespace App\Http\Controllers;

use App\Enums\BatchStatus;
use App\Enums\FeedCategory;
use App\Enums\MovementType;
use App\Models\FeedItem;
use App\Models\InventoryBatch;
use App\Models\StockMovement;
use App\Services\FeedConsumptionService;
use App\Services\InventoryService;
use App\Support\Number;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request, InventoryService $inventory, FeedConsumptionService $consumption): View
    {
        $stock = $inventory->stockMap();

        $items = FeedItem::query()
            ->where('is_active', true)
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderBy('name')
            ->get()
            ->map(function (FeedItem $item) use ($stock, $consumption, $inventory) {
                $currentStock = $stock[$item->getKey()] ?? 0.0;
                $dailyUse = $consumption->averageDailyConsumption($item, 30);

                $item->setAttribute('current_stock', $currentStock);
                $item->setAttribute('usable_stock', $inventory->usableStock($item));
                $item->setAttribute('daily_consumption', $dailyUse);
                $item->setAttribute('days_remaining', $dailyUse > 0 ? (int) floor($currentStock / $dailyUse) : null);
                $item->setAttribute('stock_value', round($currentStock * (float) $item->price_per_unit, 2));

                return $item;
            });

        $lowStock = $items->filter(fn (FeedItem $item) => (float) $item->reorder_point > 0
            && $item->current_stock <= (float) $item->reorder_point);

        return view('inventory.index', [
            'items' => $items,
            'lowStock' => $lowStock,
            'outOfStock' => $items->filter(fn (FeedItem $item) => $item->current_stock <= 0),
            'totalValue' => (float) $items->sum('stock_value'),
            'categories' => $this->enumOptions(FeedCategory::class),
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    public function batches(Request $request, InventoryService $inventory): View
    {
        $batches = InventoryBatch::query()
            ->with('feedItem')
            ->when($request->integer('feed_item_id'), fn ($q, $id) => $q->where('feed_item_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('received_date')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.batches', [
            'batches' => $batches,
            'feedItems' => FeedItem::orderBy('name')->pluck('name', 'id'),
            'statuses' => $this->enumOptions(BatchStatus::class),
            'filters' => $request->only(['feed_item_id', 'status']),
        ]);
    }

    public function movements(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['feedItem', 'batch', 'creator'])
            ->when($request->integer('feed_item_id'), fn ($q, $id) => $q->where('feed_item_id', $id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('movement_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('movement_date', '<=', $request->date('date_to')))
            ->latest('movement_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.movements', [
            'movements' => $movements,
            'feedItems' => FeedItem::orderBy('name')->pluck('name', 'id'),
            'types' => $this->enumOptions(MovementType::class),
            'filters' => $request->only(['feed_item_id', 'type', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Manual correction with a mandatory reason.
     */
    public function adjust(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'feed_item_id' => ['required', 'exists:feed_items,id'],
            'inventory_batch_id' => ['nullable', 'exists:inventory_batches,id'],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'movement_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $feedItem = FeedItem::query()->findOrFail($data['feed_item_id']);
        $batch = isset($data['inventory_batch_id']) ? InventoryBatch::query()->find($data['inventory_batch_id']) : null;

        $type = $data['direction'] === 'in'
            ? MovementType::AdjustmentIn
            : MovementType::AdjustmentOut;

        $result = $inventory->adjust(
            feedItem: $feedItem,
            type: $type,
            quantity: (float) $data['quantity'],
            reason: $data['reason'],
            batch: $batch,
            movementDate: $data['movement_date'] ?? now()->toDateString(),
            user: $request->user(),
            reference: 'ADJ-'.now()->format('YmdHis'),
        );

        $message = "Penyesuaian stok {$feedItem->name} dicatat. Stok ".Number::format($result['stock_before'], 2)
            .' → '.Number::format($result['stock_after'], 2).' '.$feedItem->unit->shortLabel().'.';

        return redirect()->route('inventory.movements')->with('status', $message);
    }
}
