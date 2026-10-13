@extends('layouts.app', ['title' => __('Health record').' '.$record->animal->animal_code])

@section('content')
    <x-page-header :title="__('Health record · :code', ['code' => $record->animal->animal_code])"
                   :subtitle="$record->record_date->translatedFormat('d M Y').' · '.$record->record_type->label()"
                   :back="route('health-records.index')"
                   :breadcrumbs="[
                       ['label' => __('Health records'), 'url' => route('health-records.index')],
                       ['label' => $record->animal->animal_code],
                   ]">
        <x-slot:actions>
            @if ($record->status->isOpen() && auth()->user()->hasAbility('health.manage'))
                <button type="button" class="btn btn-outline btn-success btn-sm" data-modal-open="resolve-modal">
                    {{ __('Mark as resolved') }}
                </button>
            @endif
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('health-records.edit', $record) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Status')" :value="$record->status->label()"
               :tone="$record->status->isOpen() ? 'warning' : 'success'" />
        <x-stat :label="__('Severity')" :value="$record->severity?->label() ?? '—'" />
        <x-stat :label="__('Cost')" :value="'Rp'.number_format((float) $record->cost, 0, ',', '.')" />
        <x-stat :label="__('Treatments')" :value="$record->treatments->count()" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Record details')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Symptoms / observations') }}</dt>
                        <dd class="mt-0.5 whitespace-pre-line">{{ $record->symptoms ?? '—' }}</dd>
                    </div>
                    <div class="rounded-field border border-base-300 p-3">
                        <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Diagnosis') }}</dt>
                        <dd class="mt-0.5 whitespace-pre-line">
                            {{ $record->diagnosis ?? __('No diagnosis yet. An observation alone is not enough to conclude a disease.') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Notes') }}</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-base-content/75">{{ $record->notes ?? '—' }}</dd>
                    </div>
                </dl>
            </x-panel>

            <x-panel :title="__('Treatments')" padding="p-0">
                @if ($record->treatments->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No treatment is linked to this record.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Medication') }}</th>
                                    <th>{{ __('Dosage') }}</th>
                                    <th>{{ __('Period') }}</th>
                                    <th>{{ __('Withdrawal') }}</th>
                                    <th class="num">{{ __('Cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($record->treatments as $treatment)
                                    <tr>
                                        <td class="font-medium">{{ $treatment->medication_name }}</td>
                                        <td class="text-base-content/70">{{ $treatment->dosage ?? '—' }}</td>
                                        <td class="whitespace-nowrap">
                                            {{ $treatment->treatment_date->translatedFormat('d M') }} – {{ $treatment->end_date?->translatedFormat('d M Y') ?? __('ongoing') }}
                                        </td>
                                        <td>
                                            {{ $treatment->withdrawal_period_days ? $treatment->withdrawal_period_days.' '.__('days') : '—' }}
                                            @if ($treatment->isWithdrawalActive())
                                                <x-badge :label="__('Active')" class="badge-soft badge-warning" />
                                            @endif
                                        </td>
                                        <td class="num"><x-money :amount="$treatment->cost ?? 0" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel :title="__('Information')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Animal') }}</dt>
                        <dd>
                            <a href="{{ route('animals.show', $record->animal) }}" class="link link-hover text-primary">
                                {{ $record->animal->animal_code }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Breed') }}</dt>
                        <dd class="text-right">{{ $record->animal->breed?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Record type') }}</dt>
                        <dd class="text-right">{{ $record->record_type->label() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Veterinarian / officer') }}</dt>
                        <dd class="text-right">{{ $record->veterinarian?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Follow-up date') }}</dt>
                        <dd class="text-right">{{ $record->follow_up_date?->translatedFormat('d M Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Resolved on') }}</dt>
                        <dd class="text-right">{{ $record->resolved_date?->translatedFormat('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-panel>

            <x-panel :title="__('Other records for this animal')" padding="p-0">
                @php
                    $others = $record->animal->healthRecords()
                        ->whereKeyNot($record->id)
                        ->orderByDesc('record_date')
                        ->limit(6)
                        ->get();
                @endphp

                @if ($others->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No other records.') }}</p>
                @else
                    <ul class="divide-y divide-base-300">
                        @foreach ($others as $other)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <a href="{{ route('health-records.show', $other) }}" class="link link-hover truncate text-xs">
                                    {{ $other->record_date->translatedFormat('d M Y') }} · {{ $other->record_type->label() }}
                                </a>
                                <x-badge :label="$other->status->label()" :class="$other->status->badgeClass()" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-panel>
        </div>
    </div>

    @if ($record->status->isOpen() && auth()->user()->hasAbility('health.manage'))
        <x-modal id="resolve-modal" :title="__('Mark record as resolved')"
                 :description="__('The history stays on file as evidence of the handling.')">
            <form method="POST" action="{{ route('health-records.resolve', $record) }}" class="mt-4 space-y-3">
                @csrf
                <x-form.input name="resolution" :label="__('Outcome')" required minlength="3"
                              :placeholder="__('For example: symptoms easing, appetite back to normal')" />
                <x-form.money name="cost" :label="__('Final cost')" :value="$record->cost ?? 0" />
                <x-form.actions>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Cancel') }}</button>
                    <x-form.submit variant="success">{{ __('Mark as resolved') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection