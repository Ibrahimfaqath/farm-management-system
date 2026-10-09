<?php

namespace App\Http\Controllers;

use App\Enums\BreedingRecordType;
use App\Http\Requests\BreedingRecordRequest;
use App\Models\Animal;
use App\Models\BreedingRecord;
use App\Services\AuditLogger;
use App\Services\BreedingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BreedingRecordController extends Controller
{
    public function index(Request $request): View
    {
        $records = BreedingRecord::query()
            ->with(['animal', 'bull'])
            ->withCount('pregnancyChecks')
            ->when($request->integer('animal_id'), fn ($q, $id) => $q->where('animal_id', $id))
            ->when($request->filled('record_type'), fn ($q) => $q->where('record_type', $request->string('record_type')))
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q
                ->where('semen_code', 'like', '%'.$request->string('search').'%'))
            ->latest('breeding_date')
            ->paginate(20)
            ->withQueryString();

        return view('breeding-records.index', [
            'records' => $records,
            'animals' => Animal::where('sex', 'female')->orderBy('animal_code')->pluck('animal_code', 'id'),
            'filters' => $request->only(['animal_id', 'record_type', 'search']),
            'recordTypes' => $this->enumOptions(BreedingRecordType::class),
            'awaitingCheck' => BreedingRecord::query()
                ->whereDoesntHave('pregnancyChecks')
                ->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $animal = $request->integer('animal_id')
            ? Animal::query()->with('breed')->findOrFail($request->integer('animal_id'))
            : null;

        $record = new BreedingRecord([
            'breeding_date' => now()->toDateString(),
            'record_type' => BreedingRecordType::Insemination,
        ]);

        return view('breeding-records.create', [
            'record' => $record,
            'animal' => $animal,
        ] + $this->formOptions());
    }

    public function store(BreedingRecordRequest $request, BreedingService $breeding, AuditLogger $audit): RedirectResponse
    {
        $record = BreedingRecord::create($request->validated() + ['created_by' => $request->user()->getKey()]);

        $audit->log(
            'breeding.recorded',
            $record,
            "Peminahan {$record->record_type->label()} pada {$record->animal->animal_code}",
            $record->animal->animal_code,
        );

        // Only actual mating attempts move the animal to "bred". Pregnancy is
        // never assumed from a mating record.
        if ($record->record_type->isMating()) {
            $breeding->recordMating($record->animal, $record->getKey(), $request->user());
        }

        return redirect()->route('pregnancy-checks.create', ['breeding_record_id' => $record->id])
            ->with('status', 'Peminahan tercatat. Status hewan: menunggu pemeriksaan kehamilan.');
    }

    public function show(BreedingRecord $record): View
    {
        $record->load(['animal.breed', 'bull', 'pregnancyChecks.veterinarian']);

        return view('breeding-records.show', ['record' => $record]);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'females' => Animal::where('sex', 'female')
                ->orderBy('animal_code')
                ->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '').' · '.$a->breeding_status->label()]),
            'bulls' => Animal::where('sex', 'male')
                ->whereIn('status', ['active'])
                ->orderBy('animal_code')
                ->pluck('animal_code', 'id'),
            'recordTypes' => $this->enumOptions(BreedingRecordType::class),
        ];
    }
}
