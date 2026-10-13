<?php

namespace App\Http\Controllers;

use App\Enums\BarnType;
use App\Http\Requests\BarnRequest;
use App\Models\Barn;
use App\Models\Farm;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarnController extends Controller
{
    public function index(Request $request): View
    {
        $barns = Barn::query()
            ->with('farm')
            ->withCount(['animals' => fn ($q) => $q->where('status', 'active')])
            ->when($request->integer('farm_id'), fn ($q, $farmId) => $q->where('farm_id', $farmId))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('barns.index', [
            'barns' => $barns,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'types' => $this->enumOptions(BarnType::class),
            // Without this the filter dropdowns cannot show which value is
            // active, so a filtered list looked unfiltered.
            'filters' => $request->only(['farm_id', 'type']),
        ]);
    }

    public function create(Request $request): View
    {
        $barn = new Barn(['type' => BarnType::Dairy, 'is_active' => true]);
        $barn->farm_id = $request->integer('farm_id') ?: Farm::query()->value('id');

        return view('barns.create', ['barn' => $barn] + $this->formOptions());
    }

    public function store(BarnRequest $request, AuditLogger $audit): RedirectResponse
    {
        $barn = Barn::create($request->validated());

        $audit->log('barn.created', $barn, "Kandang {$barn->name} dibuat", $barn->code);

        return redirect()->route('barns.show', $barn)->with('status', 'Kandang berhasil dibuat.');
    }

    public function show(Barn $barn): View
    {
        $barn->load('farm')
            ->loadCount(['animals' => fn ($q) => $q->where('status', 'active')]);

        $animals = $barn->animals()
            ->with('breed')
            ->orderBy('animal_code')
            ->paginate(25);

        $monthStart = now()->startOfMonth();

        return view('barns.show', [
            'barn' => $barn,
            'animals' => $animals,
            'milkThisMonth' => $barn->milkProductions()
                ->whereDate('production_date', '>=', $monthStart->toDateString())
                ->sum('liters'),
            'consumptionThisMonth' => $barn->feedConsumptions()
                ->whereDate('consumption_date', '>=', $monthStart->toDateString())
                ->sum('total_amount'),
        ]);
    }

    public function edit(Barn $barn): View
    {
        return view('barns.edit', ['barn' => $barn] + $this->formOptions());
    }

    public function update(BarnRequest $request, Barn $barn, AuditLogger $audit): RedirectResponse
    {
        $barn->update($request->validated());

        $audit->log('barn.updated', $barn, "Data kandang {$barn->name} diperbarui", $barn->code);

        return redirect()->route('barns.show', $barn)->with('status', 'Data kandang diperbarui.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'types' => $this->enumOptions(BarnType::class),
        ];
    }
}
