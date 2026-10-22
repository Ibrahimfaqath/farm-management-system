<?php

namespace App\Http\Controllers;

use App\Enums\PregnancyCheckMethod;
use App\Enums\PregnancyResult;
use App\Http\Requests\PregnancyCheckRequest;
use App\Models\Animal;
use App\Models\BreedingRecord;
use App\Models\PregnancyCheck;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BreedingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PregnancyCheckController extends Controller
{
    public function index(Request $request): View
    {
        $checks = PregnancyCheck::query()
            ->with(['animal', 'breedingRecord', 'veterinarian'])
            ->when($request->integer('animal_id'), fn ($q, $id) => $q->where('animal_id', $id))
            ->when($request->filled('result'), fn ($q) => $q->where('result', $request->string('result')))
            ->when($request->boolean('due_soon'), fn ($q) => $q
                ->whereIn('result', [PregnancyResult::Pregnant->value, PregnancyResult::ConfirmedPregnant->value])
                ->whereNotNull('expected_due_date')
                ->whereDate('expected_due_date', '<=', now()->addDays(21)->toDateString()))
            ->latest('check_date')
            ->paginate(20)
            ->withQueryString();

        return view('pregnancy-checks.index', [
            'checks' => $checks,
            'animals' => Animal::where('sex', 'female')->orderBy('animal_code')->get(['id', 'animal_code', 'name', 'breeding_status'])
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '').' · '.$a->breeding_status->label()]),
            'filters' => $request->only(['animal_id', 'result', 'due_soon']),
            'results' => $this->enumOptions(PregnancyResult::class),
            'pregnantCount' => PregnancyCheck::query()
                ->whereIn('result', [PregnancyResult::Pregnant->value, PregnancyResult::ConfirmedPregnant->value])
                ->count(),
            'dueSoonCount' => PregnancyCheck::query()
                ->whereIn('result', [PregnancyResult::Pregnant->value, PregnancyResult::ConfirmedPregnant->value])
                ->whereNotNull('expected_due_date')
                ->whereDate('expected_due_date', '>=', now()->toDateString())
                ->whereDate('expected_due_date', '<=', now()->addDays(21)->toDateString())
                ->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $breedingRecord = $request->integer('breeding_record_id')
            ? BreedingRecord::query()->with('animal')->findOrFail($request->integer('breeding_record_id'))
            : null;

        $check = new PregnancyCheck([
            'check_date' => now()->toDateString(),
            'method' => PregnancyCheckMethod::Rectal,
            'result' => PregnancyResult::Pregnant,
        ]);

        return view('pregnancy-checks.create', [
            'check' => $check,
            'breedingRecord' => $breedingRecord,
        ] + $this->formOptions());
    }

    public function store(PregnancyCheckRequest $request, BreedingService $breeding, AuditLogger $audit): RedirectResponse
    {
        $check = PregnancyCheck::create($request->validated() + ['created_by' => $request->user()->getKey()]);

        $breeding->applyPregnancyCheck($check, $request->user());

        $audit->log(
            'breeding.pregnancy_checked',
            $check->animal,
            "Pemeriksaan kehamilan {$check->animal->animal_code}: {$check->result->label()}",
            $check->animal->animal_code,
            ['check_id' => $check->getKey()],
        );

        $due = $check->expectedDueDate();

        $message = $check->result->isPregnant()
            ? 'Hasil pemeriksaan: hamil. Perkiraan lahir '.($due ? $due->translatedFormat('d M Y') : 'belum dapat dihitung').'.'
            : 'Hasil pemeriksaan: '.$check->result->label().'. Ternak kembali ke siklus birahi terbuka.';

        return redirect()->route('pregnancy-checks.index')->with('status', $message);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'animals' => Animal::where('sex', 'female')
                ->orderBy('animal_code')
                ->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '').' · '.$a->breeding_status->label()]),
            'breedingRecords' => BreedingRecord::query()
                ->with('animal')
                ->latest('breeding_date')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn ($r) => [$r->id => $r->animal->animal_code.' · '.$r->record_type->label().' · '.$r->breeding_date->translatedFormat('d M Y')]),
            'methods' => $this->enumOptions(PregnancyCheckMethod::class),
            'results' => $this->enumOptions(PregnancyResult::class),
            'veterinarians' => User::query()
                ->whereIn('role', ['veterinarian', 'manager', 'owner'])
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }
}
