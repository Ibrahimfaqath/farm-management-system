<?php

namespace App\Http\Controllers;

use App\Enums\MilkSession;
use App\Http\Requests\MilkProductionRequest;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Farm;
use App\Models\MilkProduction;
use App\Services\MilkProductionService;
use App\Services\ProductionAnalyticsService;
use App\Support\Number;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MilkProductionController extends Controller
{
    public function index(Request $request, ProductionAnalyticsService $analytics): View
    {
        $from = $request->date('date_from') ?? now()->subDays(29)->startOfDay();
        $to = $request->date('date_to') ?? now()->endOfDay();
        $farmId = $request->integer('farm_id') ?: null;

        $productions = MilkProduction::query()
            ->with(['animal', 'barn', 'recorder'])
            ->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
            ->when($request->integer('barn_id'), fn ($q, $id) => $q->where('barn_id', $id))
            ->when($request->integer('animal_id'), fn ($q, $id) => $q->where('animal_id', $id))
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('production_date', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('production_date', '<=', $request->date('date_to')))
            ->latest('production_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('milk-productions.index', [
            'productions' => $productions,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'barns' => Barn::with('farm')->get()->mapWithKeys(fn ($b) => [$b->id => $b->farm->name.' · '.$b->name]),
            'filters' => $request->only(['farm_id', 'barn_id', 'animal_id', 'date_from', 'date_to']),
            'summary' => $analytics->summary($farmId, $from, $to),
            'trend' => $analytics->compareWindows($farmId, $from, $to),
        ]);
    }

    public function create(Request $request): View
    {
        $animal = $request->integer('animal_id')
            ? Animal::query()->with('breed', 'barn')->findOrFail($request->integer('animal_id'))
            : null;

        return view('milk-productions.create', [
            'animal' => $animal,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'barns' => Barn::with('farm')->get()->mapWithKeys(fn ($b) => [$b->id => $b->farm->name.' · '.$b->name]),
            'animals' => Animal::query()
                ->active()
                ->lactating()
                ->with('barn')
                ->orderBy('animal_code')
                ->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.' · '.($a->barn?->name ?? 'tanpa kandang')]),
            'sessions' => $this->enumOptions(MilkSession::class),
        ]);
    }

    public function store(MilkProductionRequest $request, MilkProductionService $service): RedirectResponse
    {
        $data = $request->validated();

        $production = $service->record($data, $request->user());

        return redirect()->route('milk-productions.index')
            ->with('status', 'Produksi '.Number::format((float) $data['liters'], 1).' L tercatat pada '.$production->production_date->translatedFormat('d M Y').'.');
    }

    /**
     * Group entry page: one row per lactating animal in a barn.
     */
    public function batch(Request $request): View
    {
        $barn = Barn::query()->with('farm')->find($request->integer('barn_id'));

        return view('milk-productions.batch', [
            'barn' => $barn,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'barns' => Barn::with('farm')->get()->mapWithKeys(fn ($b) => [$b->id => $b->farm->name.' · '.$b->name]),
            'sessions' => $this->enumOptions(MilkSession::class),
        ]);
    }

    public function storeBatch(Request $request, MilkProductionService $service): RedirectResponse
    {
        $data = $request->validate([
            'farm_id' => ['required', 'exists:farms,id'],
            'barn_id' => ['nullable', 'exists:barns,id'],
            'production_date' => ['required', 'date', 'before_or_equal:today'],
            'default_session' => ['required', 'in:morning,afternoon,evening,mixed'],
            'lines' => ['nullable', 'array'],
            'lines.*.animal_id' => ['required_with:lines', 'exists:animals,id'],
            'lines.*.session' => ['nullable', 'in:morning,afternoon,evening,mixed'],
            'lines.*.liters' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'lines.*.fat_percent' => ['nullable', 'numeric', 'min:0', 'max:10'],
        ]);

        $lines = [];

        foreach ((array) ($data['lines'] ?? []) as $line) {
            if (($line['liters'] ?? null) === null || (float) $line['liters'] <= 0) {
                continue;
            }

            $lines[] = [
                'animal_id' => $line['animal_id'],
                'session' => $line['session'] ?? $data['default_session'],
                'liters' => (float) $line['liters'],
                'fat_percent' => $line['fat_percent'] ?? null,
            ];
        }

        if ($lines === []) {
            return back()->withErrors(['lines' => 'Isi minimal satu baris produksi.'])->withInput();
        }

        $count = $service->recordBatch((int) $data['farm_id'], $data['production_date'], $lines, $request->user());

        return redirect()->route('milk-productions.index')
            ->with('status', $count.' catatan produksi tersimpan.');
    }
}
