@php
    $lactating = $barn
        ? \App\Models\Animal::query()->active()->lactating()->where('barn_id', $barn->getKey())
            ->with('breed')->orderBy('animal_code')->get()
        : \App\Models\Animal::query()->active()->lactating()->with('breed')->orderBy('animal_code')->limit(60)->get();

    $existing = $barn
        ? \App\Models\MilkProduction::query()
            ->where('barn_id', $barn->getKey())
            ->whereDate('production_date', now()->toDateString())
            ->pluck('liters', 'animal_id')
        : collect();
@endphp
@extends('layouts.app', ['title' => __('Enter production per barn')])

@section('content')
    <x-page-header :title="__('Enter production per barn')"
                   :subtitle="__('Only fill in the rows for animals that were actually milked today.')"
                   :back="route('milk-productions.index')" />

    <form method="POST" action="{{ route('milk-productions.batch.store') }}" class="space-y-4">
        @csrf

        <x-panel :title="__('Context')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="farm_id" :label="__('Farm')" :options="$farms"
                               :selected="old('farm_id', $barn?->farm_id ?? $farms->keys()->first())" required />
                <x-form.select name="barn_id" :label="__('Barn')" :options="$barns" :selected="old('barn_id', $barn?->id)"
                               :placeholder="__('— All barns —')" />
                <x-form.input name="production_date" :label="__('Date')" type="date" :value="now()->toDateString()" required />
                <x-form.select name="default_session" :label="__('Default session')" :options="$sessions"
                               :selected="old('default_session', 'mixed')" required />
            </div>
            @error('lines')<p class="mt-2 text-xs text-tone-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel :title="__('Milking results')" padding="p-0">
            @if ($lactating->isEmpty())
                <div class="p-4">
                    <x-empty-state :label="__('No lactating animals yet')"
                                  :description="__('Set an animal’s production status to Lactating before recording production.')" />
                </div>
            @else
                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Animal') }}</th>
                                <th>{{ __('Breed') }}</th>
                                <th class="w-32">{{ __('Liters') }}</th>
                                <th class="w-40">{{ __('Session') }}</th>
                                <th class="w-32">{{ __('Fat %') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lactating as $index => $animal)
                                <tr>
                                    <td>
                                        <input type="hidden" name="lines[{{ $index }}][animal_id]" value="{{ $animal->id }}">
                                        <span class="block font-medium">{{ $animal->animal_code }}</span>
                                        @if ($animal->name)
                                            <span class="text-meta block truncate text-base-content/45">{{ $animal->name }}</span>
                                        @endif
                                        @if ($existing->has($animal->id))
                                            <span class="text-meta block text-base-content/45">
                                                {{ __('Recorded :liters L', ['liters' => number_format((float) $existing[$animal->id], 1)]) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-base-content/70">{{ $animal->breed?->name ?? '—' }}</td>
                                    <td>
                                        <input name="lines[{{ $index }}][liters]" type="number" step="0.1" min="0" max="200"
                                               class="input input-sm w-full bg-base-100" aria-label="{{ __('Liters for :code', ['code' => $animal->animal_code]) }}">
                                    </td>
                                    <td>
                                        <select name="lines[{{ $index }}][session]" class="select select-sm w-full bg-base-100"
                                                aria-label="{{ __('Session for :code', ['code' => $animal->animal_code]) }}">
                                            <option value="">{{ __('Use default session') }}</option>
                                            @foreach ($sessions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input name="lines[{{ $index }}][fat_percent]" type="number" step="0.01" min="0" max="10"
                                               class="input input-sm w-full bg-base-100" aria-label="{{ __('Fat % for :code', ['code' => $animal->animal_code]) }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save production') }}</x-form.submit>
                <a href="{{ route('milk-productions.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection