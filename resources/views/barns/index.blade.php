@extends('layouts.app', ['title' => __('Barns')])

@section('content')
    <x-page-header :title="__('Barns')" :subtitle="__('Housing available for livestock and feed.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('barns.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add barn') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('barns.index')" :with-search="false" columns="sm:grid-cols-3">
            <x-filter-select name="farm_id"
                :options="$farms"
                :selected="$filters['farm_id'] ?? ''"
                :placeholder="__('All farms') "
                :label="__('Filter by farm') " />

            <x-filter-select name="type"
                :options="$types"
                :selected="$filters['type'] ?? ''"
                :placeholder="__('All types') "
                :label="__('Filter by type') " />
        </x-filter-bar>

        @if ($barns->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No barns yet')"
                              :description="__('Add a barn so animal placement and stock locations stay unambiguous.')"
                              :url="route('barns.create')" :submit-label="__('Add barn')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Farm') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="num">{{ __('Occupancy') }}</th>
                            <th class="num">{{ __('Capacity') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($barns as $barn)
                            @php $percentage = $barn->occupancyPercentage(); @endphp
                            <tr>
                                <td class="font-mono text-xs">{{ $barn->code }}</td>
                                <td>
                                    <a href="{{ route('barns.show', $barn) }}" class="link link-hover font-medium text-primary">
                                        {{ $barn->name }}
                                    </a>
                                </td>
                                <td class="text-base-content/70">{{ $barn->farm->name }}</td>
                                <td>{{ $barn->type->label() }}</td>
                                <td class="num">
                                    {{-- Count and percentage belong to the same
                                         "Occupancy" column; they used to be two
                                         cells against one header, which shifted
                                         Status and Actions by a column. --}}
                                    <span class="font-medium">{{ $barn->animals_count }}</span>
                                    <span class="text-base-content/45">/ {{ $barn->capacity ?? '—' }}</span>
                                    @if ($percentage !== null)
                                        <span class="ms-1 text-base-content/55" @class([
                                            'text-tone-error' => $percentage > 100,
                                            'text-tone-warning' => $percentage >= 85 && $percentage <= 100,
                                        ])>{{ $percentage }} %</span>
                                    @endif
                                </td>
                                <td class="num">{{ $barn->capacity ?? '—' }}</td>
                                <td>
                                    <x-badge :label="$barn->is_active ? __('Active') : __('Inactive')"
                                             :class="$barn->is_active ? 'badge-soft badge-success' : 'badge-ghost'" />
                                </td>
                                <td class="row-actions">
                                        <x-actions-menu :label="__('Actions for :name', ['name' => $barn->name])">
                                            <x-menu-link :href="route('barns.show', $barn)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                            @if (auth()->user()->hasAbility('animal.manage'))
                                                <x-menu-link :href="route('barns.edit', $barn)" icon="edit">
                                                    {{ __('Edit') }}
                                                </x-menu-link>
                                            @endif
                                        </x-actions-menu>
                                    </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $barns->links() }}</div>
        @endif
    </x-panel>
@endsection