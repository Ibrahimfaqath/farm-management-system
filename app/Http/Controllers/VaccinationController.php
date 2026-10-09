<?php

namespace App\Http\Controllers;

use App\Http\Requests\VaccinationRequest;
use App\Models\Animal;
use App\Models\User;
use App\Models\Vaccination;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VaccinationController extends Controller
{
    public function index(Request $request): View
    {
        $vaccinations = Vaccination::query()
            ->with(['animal', 'veterinarian'])
            ->when($request->integer('animal_id'), fn ($q, $id) => $q->where('animal_id', $id))
            ->when($request->filled('vaccine_name'), fn ($q) => $q->where('vaccine_name', 'like', '%'.$request->string('vaccine_name').'%'))
            ->when($request->boolean('due'), fn ($q) => $q->whereNotNull('next_due_date')
                ->whereDate('next_due_date', '<=', now()->addDays(30)->toDateString()))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereNotNull('next_due_date')
                ->whereDate('next_due_date', '<', now()->toDateString()))
            ->latest('administered_date')
            ->paginate(20)
            ->withQueryString();

        return view('vaccinations.index', [
            'vaccinations' => $vaccinations,
            'animals' => Animal::orderBy('animal_code')->pluck('animal_code', 'id'),
            'filters' => $request->only(['animal_id', 'vaccine_name', 'due', 'overdue']),
            'overdueCount' => Vaccination::whereNotNull('next_due_date')
                ->whereDate('next_due_date', '<', now()->toDateString())->count(),
            'dueSoonCount' => Vaccination::whereNotNull('next_due_date')
                ->whereDate('next_due_date', '>=', now()->toDateString())
                ->whereDate('next_due_date', '<=', now()->addDays(30)->toDateString())->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $animal = $request->integer('animal_id')
            ? Animal::query()->findOrFail($request->integer('animal_id'))
            : null;

        return view('vaccinations.create', [
            'vaccination' => new Vaccination(['administered_date' => now()->toDateString()]),
            'animal' => $animal,
        ] + $this->formOptions());
    }

    public function store(VaccinationRequest $request, AuditLogger $audit): RedirectResponse
    {
        $vaccination = Vaccination::create($request->validated());

        $audit->log(
            'health.vaccinated',
            $vaccination,
            "Vaksinasi {$vaccination->vaccine_name} pada {$vaccination->animal->animal_code}",
            $vaccination->animal->animal_code,
            ['next_due_date' => $vaccination->next_due_date?->toDateString()],
        );

        return redirect()->route('vaccinations.index')->with('status', 'Vaksinasi tercatat.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'animals' => Animal::orderBy('animal_code')->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.' · '.$a->breed?->name]),
            'veterinarians' => User::query()
                ->whereIn('role', ['veterinarian', 'manager', 'owner'])
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }
}
