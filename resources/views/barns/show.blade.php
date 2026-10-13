@extends('layouts.app', ['title' => $barn->name])

@section('content')
    <x-page-header :title="$barn->name"
                   :subtitle="$barn->code.' · '.$barn->farm->name.' · '.$barn->type->label()"
                   :back="route('barns.index')"
                   :breadcrumbs="[
                       ['label' => __('Barns'), 'url' => route('barns.index')],
                       ['label' => $barn->name],
                   ]">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('animals.create') }}" class="btn btn-sm">{{ __('Add animal') }}</a>
                <a href="{{ route('feed-consumptions.create', ['barn_id' => $barn->id]) }}" class="btn btn-sm">{{ __('Record consumption') }}</a>
                <a href="{{ route('barns.edit', $barn) }}" class="btn btn-primary btn-sm">{{ __('Edit barn') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Active livestock')" :value="$barn->animals_count" tone="primary" />
        <x-stat :label="__('Capacity')" :value="$barn->capacity ?? '—'"
               :hint="$barn->capacity ? __(':count head free', ['count' => max(0, $barn->capacity - $barn->animals_count)]) : null" />
        <x-stat :label="__('Milk this month')" :value="number_format((float) $milkThisMonth, 1).' L'" />
        <x-stat :label="__('Feed this month')" :value="number_format((float) $consumptionThisMonth, 1)"
               :hint="__('All items combined')" />
    </div>

    <x-panel :title="__('Animals in this barn')" padding="p-0">
        @if ($animals->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('This barn is still empty')"
                              :description="__('No active animal is placed in this barn yet.')"
                              :url="route('animals.create')" :submit-label="__('Add animal')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Breed') }}</th>
                            <th>{{ __('Sex') }}</th>
                            <th>{{ __('Age') }}</th>
                            <th>{{ __('Production') }}</th>
                            <th>{{ __('Breeding') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($animals as $animal)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $animal) }}" class="link link-hover font-medium text-primary">
                                        {{ $animal->animal_code }}
                                    </a>
                                </td>
                                <td>{{ $animal->name ?: '—' }}</td>
                                <td class="text-base-content/70">{{ $animal->breed?->name ?? '—' }}</td>
                                <td>{{ $animal->sex->shortLabel() }}</td>
                                <td class="whitespace-nowrap text-base-content/70">{{ $animal->age_label }}</td>
                                <td><x-badge :label="$animal->production_status->label()" :class="$animal->production_status->badgeClass()" /></td>
                                <td><x-badge :label="$animal->breeding_status->label()" :class="$animal->breeding_status->badgeClass()" /></td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for :code', ['code' => $animal->animal_code])">
                                            <x-menu-link :href="route('animals.show', $animal)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $animals->links() }}</div>
        @endif
    </x-panel>
@endsection