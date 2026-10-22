<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreatmentRequest;
use App\Models\Animal;
use App\Models\HealthRecord;
use App\Models\Treatment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    public function index(Request $request): View
    {
        $treatments = Treatment::query()
            ->with(['animal', 'veterinarian', 'healthRecord'])
            ->when($request->integer('animal_id'), fn ($q, $id) => $q->where('animal_id', $id))
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q
                ->where('medication_name', 'like', '%'.$request->string('search').'%'))
            ->when($request->boolean('active_withdrawal'), fn ($q) => $q->whereNotNull('withdrawal_period_days'))
            ->latest('treatment_date')
            ->paginate(20)
            ->withQueryString();

        return view('treatments.index', [
            'treatments' => $treatments,
            'animals' => Animal::orderBy('animal_code')->get(['id', 'animal_code', 'name'])
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '')]),
            'filters' => $request->only(['animal_id', 'search', 'active_withdrawal']),
            'monthCost' => (float) Treatment::whereDate('treatment_date', '>=', now()->startOfMonth())->sum('cost'),
            'withdrawalActive' => Treatment::all()
                ->filter(fn (Treatment $treatment) => $treatment->isWithdrawalActive())
                ->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $animal = $request->integer('animal_id')
            ? Animal::query()->findOrFail($request->integer('animal_id'))
            : null;

        return view('treatments.create', [
            'treatment' => new Treatment(['treatment_date' => now()->toDateString()]),
            'animal' => $animal,
            'healthRecords' => HealthRecord::query()
                ->where('animal_id', $request->integer('animal_id'))
                ->open()
                ->orderByDesc('record_date')
                ->get(['id', 'record_date', 'diagnosis', 'symptoms', 'animal_id']),
        ] + $this->formOptions());
    }

    public function store(TreatmentRequest $request, AuditLogger $audit): RedirectResponse
    {
        $treatment = Treatment::create($request->validated());

        $audit->log(
            'health.treated',
            $treatment,
            "Pengobatan {$treatment->medication_name} pada {$treatment->animal->animal_code}",
            $treatment->animal->animal_code,
        );

        return redirect()->route('treatments.index')->with('status', 'Pengobatan tercatat.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'animals' => Animal::with('breed')->orderBy('animal_code')->get(['id', 'animal_code', 'name', 'breed_id'])
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '').($a->breed ? ' · '.$a->breed->name : '')]),
            'veterinarians' => User::query()
                ->whereIn('role', ['veterinarian', 'manager', 'owner'])
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }
}
