@extends('layouts.app', ['title' => __('Health records')])

@section('content')
    <x-page-header :title="__('Health records')"
                   :subtitle="__('Observations, check-ups and diagnoses entered by veterinary staff.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('health-records.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record health') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Open records')" :value="$openCount" :tone="$openCount > 0 ? 'warning' : 'success'" />
        <x-stat :label="__('Cost this month')" :value="'Rp'.number_format($monthCost, 0, ',', '.')" />
        <x-stat :label="__('All records')" :value="$records->total()" />
        <x-stat :label="__('Animals in care')"
               :value="App\Models\HealthRecord::open()->distinct('animal_id')->count('animal_id')" tone="info" />
    </div>

    @if ($openCount > 0)
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="trans_choice(':count health record is still open|:count health records are still open', $openCount, ['count' => $openCount])"
                         :description="__('An open record means the animal is still being monitored. The system never infers a disease from a change in production.')"
                         :url="route('health-records.index', ['open_only' => 1])" :url-label="__('Show them')" />
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('health-records.index')" :with-search="false"
                      columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="animal_id"
                :options="$animals"
                :selected="$filters['animal_id'] ?? ''"
                :placeholder="__('All animals') "
                :label="__('Filter by animal') " />

            <x-filter-select name="status"
                :options="$statuses"
                :selected="$filters['status'] ?? ''"
                :placeholder="__('All statuses') "
                :label="__('Filter by status') " />

            <x-filter-select name="severity"
                :options="$severities"
                :selected="$filters['severity'] ?? ''"
                :placeholder="__('All severities') "
                :label="__('Filter by severity') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
        </x-filter-bar>

        @if ($records->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No health records yet')"
                              :description="__('Record daily observations or veterinary check-ups to keep a traceable health history.')"
                              :url="route('health-records.create')" :submit-label="__('Record health')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Animal') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Severity') }}</th>
                            <th>{{ __('Symptoms') }}</th>
                            <th>{{ __('Diagnosis') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Veterinarian / officer') }}</th>
                            <th class="num">{{ __('Cost') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($records as $record)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $record->animal) }}" class="link link-hover font-medium text-primary">
                                        {{ $record->animal->animal_code }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap">{{ $record->record_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $record->record_type->label() }}</td>
                                <td>
                                    @if ($record->severity)
                                        <x-badge :label="$record->severity->label()" :class="$record->severity->badgeClass()" />
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="max-w-[11rem] truncate" title="{{ $record->symptoms }}">{{ $record->symptoms ?? '—' }}</td>
                                <td class="max-w-[11rem] truncate" title="{{ $record->diagnosis }}">{{ $record->diagnosis ?? '—' }}</td>
                                <td><x-badge :label="$record->status->label()" :class="$record->status->badgeClass()" /></td>
                                <td class="text-base-content/65">{{ $record->veterinarian?->name ?? '—' }}</td>
                                <td class="num"><x-money :amount="$record->cost ?? 0" /></td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for record :number', ['number' => $record->id])">
                                            <x-menu-link :href="route('health-records.show', $record)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$records" />
        @endif
    </x-panel>
@endsection