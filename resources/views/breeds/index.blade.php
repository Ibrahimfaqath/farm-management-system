@extends('layouts.app', ['title' => __('Breeds')])

@section('content')
    <x-page-header :title="__('Breeds')" :subtitle="__('Breed reference data with productivity parameters.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('breeds.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add breed') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search by name or code…')" :reset="route('breeds.index')"
                      columns="sm:grid-cols-2" />

        @if ($breeds->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No breeds registered')"
                              :description="__('Add breeds so livestock can be classified from the start.')"
                              :url="route('breeds.create')" :submit-label="__('Add breed')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Origin') }}</th>
                            <th class="num">{{ __('Avg. milk') }}</th>
                            <th class="num">{{ __('Lactation') }}</th>
                            <th class="num">{{ __('Adult weight') }}</th>
                            <th class="num">{{ __('Livestock') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($breeds as $breed)
                            <tr>
                                <td class="font-mono text-xs">{{ $breed->code }}</td>
                                <td>
                                    <span class="font-medium">{{ $breed->name }}</span>
                                    @unless ($breed->is_active)
                                        <x-badge :label="__('Inactive')" class="badge-ghost" />
                                    @endunless
                                </td>
                                <td class="text-base-content/70">{{ $breed->origin ?? '—' }}</td>
                                <td class="num">{{ $breed->average_milk_liters ? $breed->average_milk_liters.' L/hari' : '—' }}</td>
                                <td class="num">{{ $breed->average_lactation_days ? $breed->average_lactation_days.' '.__('days') : '—' }}</td>
                                <td class="num">{{ $breed->adult_weight_kg ? $breed->adult_weight_kg.' kg' : '—' }}</td>
                                <td class="num">{{ $breed->animals_count }}</td>
                                <td class="row-actions">
                                    @if (auth()->user()->hasAbility('animal.manage'))
                                        <x-actions-menu :label="__('Actions for :name', ['name' => $breed->name])">
                                            <x-menu-link :href="route('breeds.edit', $breed)" icon="edit">
                                                {{ __('Edit') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $breeds->links() }}</div>
        @endif
    </x-panel>
@endsection