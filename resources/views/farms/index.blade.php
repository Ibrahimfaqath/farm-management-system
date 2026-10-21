@extends('layouts.app', ['title' => __('Farms')])

@section('content')
    <x-page-header :title="__('Farms')" :subtitle="__('Operating units and their locations.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('settings.manage'))
                <a href="{{ route('farms.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add farm') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search by name or code…')" :reset="route('farms.index')"
                      columns="sm:grid-cols-2 lg:grid-cols-3" />

        @if ($farms->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No farms registered')"
                              :description="__('Add an operating unit before registering barns and livestock.')"
                              :url="route('farms.create')" :submit-label="__('Add farm')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th>{{ __('Manager') }}</th>
                            <th class="num">{{ __('Barns') }}</th>
                            <th class="num">{{ __('Active livestock') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($farms as $farm)
                            <tr>
                                <td class="font-mono text-xs">{{ $farm->code }}</td>
                                <td>
                                    <a href="{{ route('farms.show', $farm) }}" class="link link-hover font-medium text-primary">
                                        {{ $farm->name }}
                                    </a>
                                </td>
                                <td class="text-base-content/70">{{ collect([$farm->village, $farm->district, $farm->regency])->filter()->implode(', ') ?: '—' }}</td>
                                <td class="text-base-content/70">{{ $farm->manager_name ?? '—' }}</td>
                                <td class="num">{{ $farm->barns_count }}</td>
                                <td class="num">{{ $farm->animals_count }}</td>
                                    <td class="row-actions">
                                        <x-actions-menu :label="__('Actions for :name', ['name' => $farm->name])">
                                            <x-menu-link :href="route('farms.show', $farm)" icon="info">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                            @if (auth()->user()->hasAbility('settings.manage'))
                                                <x-menu-link :href="route('farms.edit', $farm)" icon="edit">
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
            <div class="border-t border-base-300 p-3">{{ $farms->links() }}</div>
        @endif
    </x-panel>
@endsection