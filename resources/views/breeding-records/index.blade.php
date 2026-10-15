@extends('layouts.app', ['title' => __('Breeding records')])

@section('content')
    <x-page-header :title="__('Breeding records')" :subtitle="__('Heat signs, insemination and natural mating.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('pregnancy-checks.index') }}" class="btn btn-sm">{{ __('Pregnancy checks') }}</a>
                <a href="{{ route('breeding-records.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record breeding') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($awaitingCheck > 0)
        <div class="mb-4">
            <x-alert-row tone="info"
                         :title="trans_choice(':count breeding record has not been checked yet|:count breeding records have not been checked yet', $awaitingCheck, ['count' => $awaitingCheck])"
                         :description="__('The animal status stays pending until a check. Pregnancy is never assumed from breeding alone.')"
                         :url="route('pregnancy-checks.index')" :url-label="__('Check now')" />
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Semen code…')" :reset="route('breeding-records.index')">
            <x-filter-select name="animal_id"
                :options="$animals"
                :selected="$filters['animal_id'] ?? ''"
                :placeholder="__('All females') "
                :label="__('Filter by animal') " />

            <x-filter-select name="record_type"
                :options="$recordTypes"
                :selected="$filters['record_type'] ?? ''"
                :placeholder="__('All types') "
                :label="__('Filter by type') " />
        </x-filter-bar>

        @if ($records->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No breeding records yet')"
                              :description="__('Record heat signs and breeding to build the reproductive history.')"
                              :url="route('breeding-records.create')" :submit-label="__('Record breeding')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Female') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Sire') }}</th>
                            <th>{{ __('Inseminator') }}</th>
                            <th>{{ __('Check') }}</th>
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
                                <td>{{ $record->record_type->label() }}</td>
                                <td class="whitespace-nowrap">{{ $record->breeding_date->translatedFormat('d M Y') }}</td>
                                <td class="text-meta font-mono">{{ $record->sireLabel() }}</td>
                                <td class="text-base-content/65">{{ $record->inseminator ?? '—' }}</td>
                                <td>
                                    @if ($record->pregnancy_checks_count > 0)
                                        <a href="{{ route('pregnancy-checks.index', ['animal_id' => $record->animal_id]) }}"
                                           class="link link-hover text-xs text-primary">
                                            {{ __('Checked') }}
                                        </a>
                                    @else
                                        <span class="text-xs text-base-content/45">{{ __('Not checked yet') }}</span>
                                    @endif
                                </td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for record :number', ['number' => $record->id])">
                                            <x-menu-link :href="route('breeding-records.show', $record)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $records->links() }}</div>
        @endif
    </x-panel>
@endsection