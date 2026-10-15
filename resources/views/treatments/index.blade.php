@extends('layouts.app', ['title' => __('Treatments')])

@section('content')
    <x-page-header :title="__('Treatments')"
                   :subtitle="__('Medication, dosage and the withdrawal period before milk can be consumed.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('treatments.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record treatment') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Cost this month')" :value="'Rp'.number_format($monthCost, 0, ',', '.')" />
        <x-stat :label="__('Active withdrawal periods')" :value="$withdrawalActive"
               :tone="$withdrawalActive > 0 ? 'warning' : 'success'"
               :hint="__('Milk must not be consumed yet')" />
        <x-stat :label="__('All records')" :value="$treatments->total()" />
        <x-stat :label="__('Medications used')" :value="$treatments->pluck('medication_name')->unique()->count()" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Medication name…')" :reset="route('treatments.index')">
            <x-filter-select name="animal_id"
                :options="$animals"
                :selected="$filters['animal_id'] ?? ''"
                :placeholder="__('All animals') "
                :label="__('Filter by animal') " />

            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="active_withdrawal" value="1" @checked($filters['active_withdrawal'] ?? false)
                       class="checkbox checkbox-sm checkbox-primary">
                {{ __('Has a withdrawal period') }}
            </label>
        </x-filter-bar>

        @if ($treatments->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No treatment records yet')"
                              :description="__('Record each medication with its dosage and withdrawal period so the history stays traceable.')"
                              :url="route('treatments.create')" :submit-label="__('Record treatment')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Animal') }}</th>
                            <th>{{ __('Medication') }}</th>
                            <th>{{ __('Dosage') }}</th>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Withdrawal') }}</th>
                            <th>{{ __('Veterinarian / officer') }}</th>
                            <th class="num">{{ __('Cost') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($treatments as $treatment)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $treatment->animal) }}" class="link link-hover font-medium text-primary">
                                        {{ $treatment->animal->animal_code }}
                                    </a>
                                </td>
                                <td class="font-medium">{{ $treatment->medication_name }}</td>
                                <td class="text-base-content/70">{{ $treatment->dosage ?? '—' }}</td>
                                <td class="whitespace-nowrap">
                                    {{ $treatment->treatment_date->translatedFormat('d M Y') }}
                                    @if ($treatment->end_date)
                                        <span class="text-base-content/50">– {{ $treatment->end_date->translatedFormat('d M Y') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($treatment->withdrawal_period_days)
                                        {{ $treatment->withdrawal_period_days }} {{ __('days') }}
                                        @if ($treatment->isWithdrawalActive())
                                            <x-badge :label="__('Active until :date', ['date' => $treatment->withdrawalEndsAt()->translatedFormat('d M')])"
                                                     class="badge-soft badge-warning" />
                                        @endif
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="text-base-content/65">{{ $treatment->veterinarian?->name ?? '—' }}</td>
                                <td class="num"><x-money :amount="$treatment->cost ?? 0" /></td>
                                <td class="row-actions">
                                    @if ($treatment->healthRecord)
                                        <x-actions-menu :label="__('Actions for treatment :number', ['number' => $treatment->id])">
                                            <x-menu-link :href="route('health-records.show', $treatment->healthRecord)" icon="health">
                                                {{ __('Record') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $treatments->links() }}</div>
        @endif
    </x-panel>
@endsection