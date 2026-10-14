@extends('layouts.app', ['title' => $farm->name])

@section('content')
    <x-page-header :title="$farm->name"
                   :subtitle="$farm->code.' · '.(collect([$farm->address, $farm->village, $farm->district, $farm->regency, $farm->province])->filter()->implode(', ') ?: __('Address not recorded yet'))"
                   :back="route('farms.index')"
                   :breadcrumbs="[
                       ['label' => __('Farms'), 'url' => route('farms.index')],
                       ['label' => $farm->name],
                   ]">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('barns.create', ['farm_id' => $farm->id]) }}" class="btn btn-sm">{{ __('Add barn') }}</a>
                <a href="{{ route('animals.create') }}" class="btn btn-sm">{{ __('Add animal') }}</a>
            @endif
            @if (auth()->user()->hasAbility('settings.manage'))
                <a href="{{ route('farms.edit', $farm) }}" class="btn btn-primary btn-sm">{{ __('Edit farm') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Active livestock')" :value="$farm->active_animals_count" tone="primary" />
        <x-stat :label="__('All livestock')" :value="$farm->animals_count" :hint="__('Including those that have left')" />
        <x-stat :label="__('Barns')" :value="$farm->barns->count()" />
        <x-stat :label="__('Land area')" :value="$farm->area_hectares ? $farm->area_hectares.' ha' : '—'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            <x-panel :title="__('Barns')" padding="p-0">
                @if ($farm->barns->isEmpty())
                    <div class="p-4">
                        <x-empty-state :label="__('No barns yet')"
                                      :description="__('Add a barn to place animals and record production.')"
                                      :url="route('barns.create', ['farm_id' => $farm->id])" :submit-label="__('Add barn')" />
                    </div>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th class="num">{{ __('Occupancy') }}</th>
                                    <th class="num">{{ __('Capacity') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($farm->barns as $barn)
                                    <tr>
                                        <td class="font-mono text-xs">{{ $barn->code }}</td>
                                        <td>
                                            <a href="{{ route('barns.show', $barn) }}" class="link link-hover font-medium text-primary">
                                                {{ $barn->name }}
                                            </a>
                                        </td>
                                        <td>{{ $barn->type->label() }}</td>
                                        <td class="num">{{ $barn->animals_count }}</td>
                                        <td class="num">{{ $barn->capacity ?? '—' }}</td>
                                        <td>
                                            <x-badge :label="$barn->is_active ? __('Active') : __('Inactive')"
                                                     :class="$barn->is_active ? 'badge-soft badge-success' : 'badge-ghost'" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Information')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Manager') }}</dt>
                    <dd class="text-right">{{ $farm->manager_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Phone') }}</dt>
                    <dd class="text-right">{{ $farm->phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Established') }}</dt>
                    <dd class="text-right">{{ $farm->established_date?->translatedFormat('M Y') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Postal code') }}</dt>
                    <dd class="text-right">{{ $farm->postal_code ?? '—' }}</dd>
                </div>
            </dl>
            @if ($farm->notes)
                <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $farm->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection