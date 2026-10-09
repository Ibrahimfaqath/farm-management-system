<?php

namespace App\Http\Controllers;

use App\Enums\AcquisitionType;
use App\Enums\AnimalStatus;
use App\Enums\BirthType;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Http\Requests\BirthRecordRequest;
use App\Models\Animal;
use App\Models\BirthRecord;
use App\Models\PregnancyCheck;
use App\Services\AuditLogger;
use App\Services\BreedingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BirthRecordController extends Controller
{
    public function index(Request $request): View
    {
        $births = BirthRecord::query()
            ->with(['dam', 'sire', 'animal', 'pregnancyCheck'])
            ->when($request->integer('dam_id'), fn ($q, $id) => $q->where('dam_id', $id))
            ->when($request->filled('birth_type'), fn ($q) => $q->where('birth_type', $request->string('birth_type')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('birth_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('birth_date', '<=', $request->date('date_to')))
            ->latest('birth_date')
            ->paginate(20)
            ->withQueryString();

        return view('birth-records.index', [
            'births' => $births,
            'dams' => Animal::where('sex', 'female')->orderBy('animal_code')->pluck('animal_code', 'id'),
            'filters' => $request->only(['dam_id', 'birth_type', 'date_from', 'date_to']),
            'birthTypes' => $this->enumOptions(BirthType::class),
            'yearCount' => BirthRecord::whereDate('birth_date', '>=', now()->subYear())->count(),
            'difficultCount' => BirthRecord::whereIn('birth_type', [
                BirthType::Difficult->value,
                BirthType::Caesarean->value,
                BirthType::Stillborn->value,
            ])->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $dam = $request->integer('dam_id')
            ? Animal::query()->with('breed')->findOrFail($request->integer('dam_id'))
            : null;

        $check = $request->integer('pregnancy_check_id')
            ? PregnancyCheck::query()->with('animal')->findOrFail($request->integer('pregnancy_check_id'))
            : null;

        $birth = new BirthRecord([
            'birth_date' => now()->toDateString(),
            'birth_type' => BirthType::Normal,
        ]);

        return view('birth-records.create', [
            'birth' => $birth,
            'dam' => $dam ?? $check?->animal,
            'pregnancyCheck' => $check,
        ] + $this->formOptions());
    }

    public function store(BirthRecordRequest $request, BreedingService $breeding, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();

        $result = DB::transaction(function () use ($data, $request, $breeding) {
            $calfId = null;

            // A live calf becomes a real animal record with genealogy links.
            if ($data['birth_type'] !== BirthType::Stillborn->value && ($data['register_calf'] ?? false)) {
                $calf = Animal::create([
                    'farm_id' => Animal::findOrFail($data['dam_id'])->farm_id,
                    'barn_id' => Animal::findOrFail($data['dam_id'])->barn_id,
                    'current_barn_id' => Animal::findOrFail($data['dam_id'])->barn_id,
                    'breed_id' => Animal::findOrFail($data['dam_id'])->breed_id,
                    'animal_code' => $data['calf_code'],
                    'name' => $data['calf_name'] ?? null,
                    'status' => AnimalStatus::Active,
                    'production_status' => ProductionStatus::Calf,
                    'breeding_status' => BreedingStatus::Open,
                    'sex' => $data['calf_sex'],
                    'date_of_birth' => $data['birth_date'],
                    'acquisition_date' => $data['birth_date'],
                    'acquisition_type' => AcquisitionType::Birth,
                    'created_by' => $request->user()->getKey(),
                ]);

                $calfId = $calf->getKey();
            }

            return $breeding->recordBirth([
                'dam_id' => $data['dam_id'],
                'sire_id' => $data['sire_id'] ?? null,
                'pregnancy_check_id' => $data['pregnancy_check_id'] ?? null,
                'birth_date' => $data['birth_date'],
                'birth_type' => $data['birth_type'],
                'birth_weight_kg' => $data['birth_weight_kg'] ?? null,
                'animal_id' => $calfId,
            ], $request->user());
        });

        $audit->log('breeding.birth_recorded', $result, 'Kelahiran dicatat', $result->animal?->animal_code);

        return redirect()->route('birth-records.index')
            ->with('status', $result->animal
                ? "Kelahiran tercatat. Anak didaftarkan sebagai {$result->animal->animal_code}."
                : 'Kelahiran tercatat tanpa anak yang didaftarkan.');
    }

    public function show(BirthRecord $birth): View
    {
        $birth->load(['dam.breed', 'sire', 'animal', 'pregnancyCheck']);

        return view('birth-records.show', ['birth' => $birth]);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'dams' => Animal::where('sex', 'female')
                ->orderBy('animal_code')
                ->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '').' · '.$a->breeding_status->label()]),
            'sires' => Animal::where('sex', 'male')->orderBy('animal_code')->pluck('animal_code', 'id'),
            'pregnancyChecks' => PregnancyCheck::query()
                ->with('animal')
                ->whereIn('result', ['pregnant', 'confirmed_pregnant'])
                ->orderByDesc('check_date')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn ($c) => [$c->id => $c->animal->animal_code.' · '.$c->check_date->translatedFormat('d M Y')]),
            'birthTypes' => $this->enumOptions(BirthType::class),
            'sexOptions' => $this->enumOptions(Sex::class),
        ];
    }
}
