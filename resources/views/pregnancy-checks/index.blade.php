@extends('layouts.app', ['title' => __('Pregnancy checks')])

@section('content')
    <x-page-header :title="__('Pregnancy checks')"
                   :subtitle="__('Check results that determine pregnancy status and the expected birth date.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('birth-records.index') }}" class="btn btn-sm">{{ __('Births') }}</a>
                <a href="{{ route('pregnancy-checks.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record check') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Pregnant animals')" :value="$pregnantCount" tone="primary" />
        <x-stat :label="__('Births due in 21 days')" :value="$dueSoonCount"
               :tone="$dueSoonCount > 0 ? 'warning' : 'success'" />
        <x-stat :label="__('All checks')" :value="$checks->total()" />
        <x-stat :label="__('Animals checked')" :value="$checks->pluck('animal_id')->unique()->count()" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('pregnancy-checks.index')" :with-search="false">
            <x-filter-select name="animal_id"
                :options="$animals"
                :selected="$filters['animal_id'] ?? ''"
                :placeholder="__('All females') "
                :label="__('Filter by animal') " />

            <x-filter-select name="result"
                :options="$results"
                :selected="$filters['result'] ?? ''"
                :placeholder="__('All results') "
                :label="__('Filter by result') " />

            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="due_soon" value="1" @checked($filters['due_soon'] ?? false)
                       class="checkbox checkbox-sm checkbox-primary">
                {{ __('Birth due in 21 days') }}
            </label>
        </x-filter-bar>

        @if ($checks->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No pregnancy checks yet')"
                              :description="__('A pregnancy check confirms the status and calculates the expected birth date.')"
                              :url="route('pregnancy-checks.create')" :submit-label="__('Record check')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Female') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Result') }}</th>
                            <th>{{ __('Gestation age') }}</th>
                            <th>{{ __('Expected birth') }}</th>
                            <th class="num">{{ __('Foetuses') }}</th>
                            <th>{{ __('Veterinarian / officer') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($checks as $check)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $check->animal) }}" class="link link-hover font-medium text-primary">
                                        {{ $check->animal->animal_code }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap">{{ $check->check_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $check->method->label() }}</td>
                                <td><x-badge :label="$check->result->label()" :class="$check->result->badgeClass()" /></td>
                                <td class="num">{{ $check->estimated_gestation_days ? $check->estimated_gestation_days.' '.__('days') : '—' }}</td>
                                <td class="whitespace-nowrap">{{ $check->expectedDueDate()?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td class="num">{{ $check->fetus_count ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $check->veterinarian?->name ?? '—' }}</td>
                                <td class="row-actions">
                                    @if ($check->result->isPregnant() && auth()->user()->hasAbility('breeding.manage'))
                                        <x-actions-menu :label="__('Actions for check :number', ['number' => $check->id])">
                                            <x-menu-link :href="route('birth-records.create', ['pregnancy_check_id' => $check->id])" icon="plus">
                                                {{ __('Record birth') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$checks" />
        @endif
    </x-panel>
@endsection