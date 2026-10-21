<?php

namespace App\Http\Controllers;

use App\Enums\FeedCategory;
use App\Enums\UnitOfMeasure;
use App\Http\Requests\FeedItemRequest;
use App\Models\FeedItem;
use App\Services\AuditLogger;
use App\Services\CodeGenerator;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedItemController extends Controller
{
    public function index(Request $request, InventoryService $inventory): View
    {
        $stock = $inventory->stockMap();

        $feedItems = FeedItem::query()
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereIn('id', $inventory->lowStockItems()->modelKeys()))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (FeedItem $item) => $item->setAttribute(
                'current_stock',
                $stock[$item->getKey()] ?? 0.0,
            ));

        return view('feed-items.index', [
            'feedItems' => $feedItems,
            'filters' => $request->only(['search', 'category', 'low_stock']),
            'categories' => $this->enumOptions(FeedCategory::class),
            'lowStockCount' => $inventory->lowStockItems()->count(),
        ]);
    }

    public function create(CodeGenerator $codes): View
    {
        return view('feed-items.create', [
            'feedItem' => new FeedItem([
                'category' => FeedCategory::Concentrate,
                'unit' => UnitOfMeasure::Kilogram,
                'is_active' => true,
            ]),
            'nextCode' => $codes->next('feed_items', 'code', 'FD-'),
        ] + $this->formOptions());
    }

    public function store(FeedItemRequest $request, AuditLogger $audit, CodeGenerator $codes): RedirectResponse
    {
        $feedItem = DB::transaction(fn () => FeedItem::create($request->validated() + [
            'code' => $codes->next('feed_items', 'code', 'FD-'),
        ]));

        $audit->log('feed_item.created', $feedItem, "Item pakan {$feedItem->name} ditambahkan", $feedItem->code);

        return redirect()->route('inventory.index')->with('status', "Item pakan {$feedItem->name} berhasil ditambahkan.");
    }

    public function edit(FeedItem $feedItem): View
    {
        return view('feed-items.edit', ['feedItem' => $feedItem] + $this->formOptions());
    }

    public function update(FeedItemRequest $request, FeedItem $feedItem, AuditLogger $audit): RedirectResponse
    {
        $feedItem->update($request->validated());

        $audit->log('feed_item.updated', $feedItem, "Data item pakan {$feedItem->name} diperbarui", $feedItem->code);

        return redirect()->route('inventory.index')->with('status', 'Data item pakan diperbarui.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'categories' => $this->enumOptions(FeedCategory::class),
            'units' => $this->enumOptions(UnitOfMeasure::class),
        ];
    }
}
