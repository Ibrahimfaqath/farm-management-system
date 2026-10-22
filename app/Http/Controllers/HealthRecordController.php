<?php

namespace App\Http\Controllers;

use App\Enums\HealthRecordStatus;
use App\Enums\HealthRecordType;
use App\Enums\HealthSeverity;
use App\Http\Requests\HealthRecordRequest;
use App\Models\Animal;
use App\Models\HealthRecord;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HealthRecordController extends Controller
{
    public function index(Request $request): View
    {
        $records = HealthRecord::query()
            ->with(['animal', 'veterinarian'])
            ->when($request->integer('animal_id'), fn ($q, $id) => $q->where('animal_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->when($request->boolean('open_only'), fn ($q) => $q->open())
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('record_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('record_date', '<=', $request->date('date_to')))
            ->latest('record_date')
            ->paginate(20)
            ->withQueryString();

        return view('health-records.index', [
            'records' => $records,
            'animals' => Animal::orderBy('animal_code')->get(['id', 'animal_code', 'name'])
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.($a->name ? ' · '.$a->name : '')]),
            'filters' => $request->only(['animal_id', 'status', 'severity', 'open_only', 'date_from', 'date_to']),
            'statuses' => $this->enumOptions(HealthRecordStatus::class),
            'severities' => $this->enumOptions(HealthSeverity::class),
            'openCount' => HealthRecord::open()->count(),
            'monthCost' => (float) HealthRecord::whereDate('record_date', '>=', now()->startOfMonth())->sum('cost'),
        ]);
    }

    public function create(Request $request): View
    {
        $animal = $request->integer('animal_id')
            ? Animal::query()->with('breed')->findOrFail($request->integer('animal_id'))
            : null;

        $record = new HealthRecord([
            'record_date' => now()->toDateString(),
            'record_type' => HealthRecordType::Observation,
            'status' => HealthRecordStatus::Open,
        ]);

        return view('health-records.create', ['record' => $record, 'animal' => $animal] + $this->formOptions());
    }

    public function store(HealthRecordRequest $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();

        $record = HealthRecord::create($data + ['created_by' => $request->user()->getKey()]);

        $audit->log(
            'health.recorded',
            $record,
            "Rekam kesehatan {$record->animal->animal_code}: {$record->record_type->label()}",
            $record->animal->animal_code,
        );

        return redirect()->route('health-records.show', $record)
            ->with('status', "Rekam kesehatan untuk {$record->animal->animal_code} tersimpan.");
    }

    public function show(HealthRecord $record): View
    {
        $record->load(['animal.breed', 'veterinarian', 'treatments.veterinarian']);

        return view('health-records.show', ['record' => $record]);
    }

    public function edit(HealthRecord $record): View
    {
        return view('health-records.edit', ['record' => $record] + $this->formOptions());
    }

    public function update(HealthRecordRequest $request, HealthRecord $record, AuditLogger $audit): RedirectResponse
    {
        $record->update($request->validated());

        $audit->log('health.updated', $record, "Rekam kesehatan {$record->animal->animal_code} diperbarui", $record->animal->animal_code);

        return redirect()->route('health-records.show', $record)->with('status', 'Rekam kesehatan diperbarui.');
    }

    /**
     * Close a health record while keeping the history.
     */
    public function resolve(Request $request, HealthRecord $record, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'min:3', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'resolution.required' => 'Tuliskan hasil penanganan.',
        ]);

        $record->update([
            'status' => HealthRecordStatus::Resolved,
            'resolved_date' => now()->toDateString(),
            'notes' => trim(($record->notes ?? '')."\nHasil penanganan: ".$data['resolution']),
            'cost' => $data['cost'] ?? $record->cost,
        ]);

        $audit->log('health.resolved', $record, "Rekam kesehatan {$record->animal->animal_code} diselesaikan", $record->animal->animal_code);

        return redirect()->route('health-records.show', $record)->with('status', 'Rekam kesehatan ditandai selesai.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'animals' => Animal::with('breed')->orderBy('animal_code')->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->animal_code.' · '.$a->sex->label().($a->name ? ' · '.$a->name : '')]),
            'recordTypes' => $this->enumOptions(HealthRecordType::class),
            'severities' => $this->enumOptions(HealthSeverity::class),
            'statuses' => $this->enumOptions(HealthRecordStatus::class),
            'veterinarians' => User::query()
                ->whereIn('role', ['veterinarian', 'manager', 'owner'])
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }
}
