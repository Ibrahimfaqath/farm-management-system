<?php

namespace App\Http\Controllers;

use App\Enums\AcquisitionType;
use App\Enums\AnimalStatus;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Http\Requests\AnimalExitRequest;
use App\Http\Requests\AnimalRequest;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Breed;
use App\Models\Farm;
use App\Services\AnimalExitService;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index(Request $request): View
    {
        $animals = Animal::query()
            ->with(['breed', 'barn'])
            ->withCount(['openHealthRecords'])
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('animal_code', 'like', '%'.$request->string('search').'%')
                ->orWhere('name', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('production_status'), fn ($q) => $q->where('production_status', $request->string('production_status')))
            ->when($request->filled('breeding_status'), fn ($q) => $q->where('breeding_status', $request->string('breeding_status')))
            ->when($request->filled('sex'), fn ($q) => $q->where('sex', $request->string('sex')))
            ->when($request->filled('barn_id'), fn ($q) => $q->where('barn_id', $request->integer('barn_id')))
            ->when($request->filled('breed_id'), fn ($q) => $q->where('breed_id', $request->integer('breed_id')))
            ->when($request->boolean('health_issue'), fn ($q) => $q->withOpenHealthIssue())
            ->orderBy('animal_code')
            ->paginate(20)
            ->withQueryString();

        return view('animals.index', [
            'animals' => $animals,
            'filters' => $request->only(['search', 'status', 'production_status', 'breeding_status', 'sex', 'barn_id', 'breed_id', 'health_issue']),
            'statusOptions' => $this->enumOptions(AnimalStatus::class),
            'productionOptions' => $this->enumOptions(ProductionStatus::class),
            'breedingOptions' => $this->enumOptions(BreedingStatus::class),
            'sexOptions' => $this->enumOptions(Sex::class),
            'barns' => Barn::with('farm')->get()->mapWithKeys(fn ($b) => [$b->id => $b->farm->name.' · '.$b->name]),
            'breeds' => Breed::orderBy('name')->pluck('name', 'id'),
            'summary' => $this->summary(),
        ]);
    }

    public function create(Request $request): View
    {
        $animal = new Animal([
            'status' => AnimalStatus::Active,
            'production_status' => ProductionStatus::Calf,
            'breeding_status' => BreedingStatus::Open,
            'sex' => Sex::Female,
            'date_of_birth' => now()->toDateString(),
        ]);

        return view('animals.create', $this->formData($animal, $request));
    }

    public function store(AnimalRequest $request, AuditLogger $audit): RedirectResponse
    {
        $animal = Animal::create($request->validated() + [
            'current_barn_id' => $request->input('barn_id'),
            'created_by' => $request->user()->getKey(),
        ]);

        $audit->log('animal.created', $animal, "Ternak {$animal->animal_code} didaftarkan", $animal->animal_code, [
            'breed_id' => $animal->breed_id,
            'sex' => $animal->sex->value,
        ]);

        return redirect()->route('animals.show', $animal)->with('status', "Ternak {$animal->animal_code} berhasil didaftarkan.");
    }

    public function show(Animal $animal): View
    {
        $animal->load([
            'farm', 'barn', 'breed', 'dam:id,animal_code,name,sex', 'sire:id,animal_code,name,sex',
            'calves:id,animal_code,name,sex,date_of_birth,production_status,status',
            'vaccinations' => fn ($q) => $q->orderByDesc('administered_date')->limit(8),
            'treatments' => fn ($q) => $q->orderByDesc('treatment_date')->limit(8),
            'healthRecords' => fn ($q) => $q->orderByDesc('record_date')->limit(8),
            'breedingRecords' => fn ($q) => $q->orderByDesc('breeding_date')->limit(8),
        ])->loadCount(['milkProductions']);

        return view('animals.show', [
            'animal' => $animal,
            'latestPregnancyCheck' => $animal->latestPregnancyCheck()->with('veterinarian')->first(),
            'birthRecord' => $animal->birthRecordsAsDam()->latest('birth_date')->first(),
            'milkSummary' => $animal->milkProductions()
                ->whereDate('production_date', '>=', now()->subDays(30)->toDateString())
                ->selectRaw('SUM(liters) as total, AVG(liters) as average, COUNT(*) as records')
                ->first(),
            'latestVaccination' => $animal->vaccinations()->orderByDesc('next_due_date')->first(),
        ]);
    }

    public function edit(Animal $animal, Request $request): View
    {
        return view('animals.edit', $this->formData($animal, $request));
    }

    public function update(AnimalRequest $request, Animal $animal, AuditLogger $audit): RedirectResponse
    {
        $animal->update($request->validated() + ['current_barn_id' => $request->input('barn_id')]);

        $audit->log('animal.updated', $animal, "Data ternak {$animal->animal_code} diperbarui", $animal->animal_code);

        return redirect()->route('animals.show', $animal)->with('status', 'Data ternak diperbarui.');
    }

    /**
     * Exit an animal (sold, deceased, transferred) without removing history.
     */
    public function exit(AnimalExitRequest $request, Animal $animal, AnimalExitService $service): RedirectResponse
    {
        $result = $service->record($animal, $request->validated(), $request->user());

        return redirect()
            ->route('animals.show', $animal)
            ->with('status', $result['message']);
    }

    public function reopen(Animal $animal, AuditLogger $audit): RedirectResponse
    {
        abort_unless($animal->status !== AnimalStatus::Active, 404);

        $audit->log('animal.reopened', $animal, "Ternak {$animal->animal_code} dikembalikan menjadi aktif", $animal->animal_code);

        $animal->update([
            'status' => AnimalStatus::Active,
            'exit_date' => null,
            'exit_type' => null,
            'exit_value' => 0,
            'exit_reference' => null,
            'exit_notes' => null,
        ]);

        return redirect()
            ->route('animals.show', $animal)
            ->with('status', "Ternak {$animal->animal_code} kini berstatus aktif kembali.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Animal $animal, Request $request): array
    {
        return [
            'animal' => $animal,
            'farms' => Farm::orderBy('name')->pluck('name', 'id'),
            'barns' => Barn::with('farm')->get()->mapWithKeys(fn ($b) => [$b->id => $b->farm->name.' · '.$b->name]),
            'breeds' => Breed::orderBy('name')->pluck('name', 'id'),
            'parents' => Animal::query()
                ->when($animal->exists, fn ($q) => $q->whereKeyNot($animal->getKey()))
                ->orderBy('animal_code')
                ->get(['id', 'animal_code', 'name', 'sex'])
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '').' ('.$a->sex->shortLabel().')']),
            'statusOptions' => $this->enumOptions(AnimalStatus::class),
            'productionOptions' => $this->enumOptions(ProductionStatus::class),
            'breedingOptions' => $this->enumOptions(BreedingStatus::class),
            'sexOptions' => $this->enumOptions(Sex::class),
            'acquisitionOptions' => $this->enumOptions(AcquisitionType::class),
            'exitTypes' => [
                AnimalStatus::Sold->value => 'Terjual',
                AnimalStatus::Deceased->value => 'Mati',
                AnimalStatus::Transferred->value => 'Dipindahkan',
            ],
        ];
    }

    /** @return array<string, int> */
    private function summary(): array
    {
        return [
            'total' => Animal::query()->count(),
            'active' => Animal::query()->active()->count(),
            'lactating' => Animal::query()->active()->lactating()->count(),
            'pregnant' => Animal::query()->active()->pregnant()->count(),
            'calves' => Animal::query()->active()->where('production_status', ProductionStatus::Calf->value)->count(),
            'sold' => Animal::query()->where('status', AnimalStatus::Sold->value)->count(),
            'deceased' => Animal::query()->where('status', AnimalStatus::Deceased->value)->count(),
            'transferred' => Animal::query()->where('status', AnimalStatus::Transferred->value)->count(),
        ];
    }
}
