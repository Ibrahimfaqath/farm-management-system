@extends('layouts.app', ['title' => __('Births')])

@section('content')
    <x-page-header :title="__('Births')" :subtitle="__('Links the dam, the sire and the calf born.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('birth-records.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record birth') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Births in 12 months')" :value="$yearCount" />
        <x-stat :label="__('Normal')" :value="$births->where('birth_type.value', 'normal')->count()" tone="success" />
        <x-stat :label="__('Needed assistance')" :value="$difficultCount"
               :tone="$difficultCount > 0 ? 'warning' : 'success'" />
        <x-stat :label="__('Calves registered')" :value="$births->whereNotNull('animal_id')->count()" tone="primary" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('birth-records.index')" :with-search="false"
                      columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="dam_id"
                :options="$dams"
                :selected="$filters['dam_id'] ?? ''"
                :placeholder="__('All dams') "
                :label="__('Filter by dam') " />

            <x-filter-select name="birth_type"
                :options="$birthTypes"
                :selected="$filters['birth_type'] ?? ''"
                :placeholder="__('All birth types') "
                :label="__('Filter by birth type') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('To date') }}">
        </x-filter-bar>

        @if ($births->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No birth records yet')"
                              :description="__('Record a birth to build the pedigree, return the dam to production and register the calf.')"
                              :url="route('birth-records.create')" :submit-label="__('Record birth')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Dam') }}</th>
                            <th>{{ __('Sire') }}</th>
                            <th>{{ __('Calf') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="num">{{ __('Birth weight') }}</th>
                            <th>{{ __('Colostrum') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($births as $birth)
                            <tr>
                                <td class="whitespace-nowrap">{{ $birth->birth_date->translatedFormat('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('animals.show', $birth->dam) }}" class="link link-hover font-medium text-primary">
                                        {{ $birth->dam->animal_code }}
                                    </a>
                                </td>
                                <td class="text-base-content/70">{{ $birth->sire?->animal_code ?? '—' }}</td>
                                <td>
                                    @if ($birth->animal)
                                        <a href="{{ route('animals.show', $birth->animal) }}" class="link link-hover font-medium text-primary">
                                            {{ $birth->animal->animal_code }}
                                        </a>
                                    @else
                                        <span class="text-base-content/45">{{ __('No live calf') }}</span>
                                    @endif
                                </td>
                                <td><x-badge :label="$birth->birth_type->label()" :class="$birth->birth_type->badgeClass()" /></td>
                                <td class="num">{{ $birth->birth_weight_kg ? $birth->birth_weight_kg.' kg' : '—' }}</td>
                                <td class="text-base-content/65">{{ $birth->colostrum_intake ?? '—' }}</td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for :number', ['number' => $birth->birth_number ?? $birth->id])">
                                            <x-menu-link :href="route('birth-records.show', $birth)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $births->links() }}</div>
        @endif
    </x-panel>
@endsection
