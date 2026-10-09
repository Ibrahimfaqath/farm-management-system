<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedConsumptionRequest;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Farm;
use App\Models\FeedConsumption;
use App\Models\FeedItem;
use App\Services\FeedConsumptionService;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedConsumptionController extends Controller
{
    public function index(Request $request): View
    {
        $consumptions = FeedConsumption::query()
            ->with(['barn', 'creator', 'items.feedItem'])
            ->withCount('items')
            ->when($request->integer('barn_id'), fn ($q, $id) => $q->where('barn_id', $id))
            ->when($request->integer('farm_id'), fn ($q, $id) => $q->where('farm_id', $id))
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('consumption_date', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('consumption_date', '<=', $request->date('date_to')))
            ->latest('consumption_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('feed-consumptions.index', [
            'consumptions' => $consumptions,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'barns' => Barn::with('farm')->get()->mapWithKeys(fn ($b) => [$b->id => $b->farm->name.' · '.$b->name]),
            'filters' => $request->only(['farm_id', 'barn_id', 'date_from', 'date_to']),
            'monthTotal' => (float) FeedConsumption::query()
                ->whereDate('consumption_date', '>=', now()->startOfMonth())
                ->sum('total_amount'),
            'monthQuantity' => (float) FeedConsumption::query()
                ->whereDate('consumption_date', '>=', now()->startOfMonth())
                ->sum('total_quantity'),
        ]);
    }

    public function create(Request $request, InventoryService $inventory): View
    {
        $barn = $request->integer('barn_id')
            ? Barn::query()->with('farm')->findOrFail($request->integer('barn_id'))
            : null;

        $feedItems = FeedItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (FeedItem $item) use ($inventory) {
                $item->setAttribute('current_stock', $inventory->stockOf($item));
                $item->setAttribute('usable_stock', $inventory->usableStock($item));

                return $item;
            });

        return view('feed-consumptions.create', [
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'barns' => Barn::with('farm')->get()->mapWithKeys(fn ($b) => [$b->id => $b->farm->name.' · '.$b->name]),
            'barn' => $barn,
            'feedItems' => $feedItems,
            'batchesByItem' => $feedItems->mapWithKeys(fn (FeedItem $item) => [
                $item->getKey() => $inventory->usableBatches($item)->map(fn ($batch) => [
                    'id' => $batch->getKey(),
                    'label' => $batch->batch_code.' · sisa '.number_format((float) $batch->quantity_remaining, 2).' '.$item->unit->shortLabel(),
                ])->values(),
            ]),
            'animalsByBarn' => Barn::query()
                ->with(['farm', 'animals' => fn ($q) => $q->where('status', 'active')->orderBy('animal_code')])
                ->get()
                ->mapWithKeys(fn ($b) => [
                    $b->id => $b->animals->mapWithKeys(fn (Animal $a) => [
                        $a->getKey() => $a->animal_code.($a->name ? ' · '.$a->name : ''),
                    ]),
                ]),
        ]);
    }

    public function store(FeedConsumptionRequest $request, FeedConsumptionService $service): RedirectResponse
    {
        $data = $request->validated();

        $result = $service->record([
            'farm_id' => $data['farm_id'],
            'barn_id' => $data['barn_id'] ?? null,
            'consumption_date' => $data['consumption_date'],
            'animal_count' => $data['animal_count'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'allow_negative_stock' => $data['allow_negative_stock'] ?? false,
            'override_reason' => $data['override_reason'] ?? null,
            'items' => $data['items'],
        ], $request->user());

        return redirect()
            ->route('feed-consumptions.show', $result['consumption'])
            ->with([
                'status' => 'Tercatat: '.number_format($result['total_quantity'], 2).' unit / Rp'.number_format($result['total_amount'], 0).'.',
                'impact' => $result['lines'],
            ]);
    }

    public function show(FeedConsumption $consumption): View
    {
        $consumption->load(['farm', 'barn', 'creator', 'overrideBy'])
            ->load(['items.feedItem', 'items.batch', 'allocations.animal']);

        return view('feed-consumptions.show', [
            'consumption' => $consumption,
            'movements' => $consumption->movements()->with('batch', 'feedItem')->get(),
            'groupedByAnimal' => $consumption->allocations
                ->filter(fn ($allocation) => $allocation->animal !== null)
                ->groupBy('animal_id'),
        ]);
    }

    public function reverse(Request $request, FeedConsumption $consumption, FeedConsumptionService $service): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:255'],
        ], [
            'reason.required' => 'Alasan koreksi wajib diisi.',
        ]);

        $result = $service->reverse($consumption, $data['reason'], $request->user());

        return redirect()->route('feed-consumptions.show', $consumption)
            ->with('status', 'Pemakaian dikoreksi. Stok dikembalikan '.number_format($result['quantity'], 2).' unit.');
    }
}
