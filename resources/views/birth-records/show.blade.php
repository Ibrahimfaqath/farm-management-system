@extends('layouts.app', ['title' => __('Birth').' '.$birth->dam->animal_code])

@section('content')
    <x-page-header :title="__('Birth · :code', ['code' => $birth->dam->animal_code])"
                   :subtitle="$birth->birth_date->translatedFormat('d M Y').' · '.$birth->birth_type->label()"
                   :back="route('birth-records.index')"
                   :breadcrumbs="[
                       ['label' => __('Births'), 'url' => route('birth-records.index')],
                       ['label' => $birth->dam->animal_code],
                   ]" />

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Dam')" :value="$birth->dam->animal_code" />
        <x-stat :label="__('Sire')" :value="$birth->sire?->animal_code ?? '—'" />
        <x-stat :label="__('Calf')" :value="$birth->animal?->animal_code ?? __('None')"
               :tone="$birth->animal ? 'success' : 'warning'" />
        <x-stat :label="__('Birth weight')" :value="$birth->birth_weight_kg ? $birth->birth_weight_kg.' kg' : '—'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Birth details')">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-base-content/55">{{ __('Date') }}</dt>
                        <dd>{{ $birth->birth_date->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Time') }}</dt>
                        <dd>{{ $birth->birth_time ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Birth type') }}</dt>
                        <dd>{{ $birth->birth_type->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Colostrum') }}</dt>
                        <dd>{{ $birth->colostrum_intake ?? '—' }}</dd>
                    </div>
                </dl>
                @if ($birth->assistance_notes)
                    <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $birth->assistance_notes }}</p>
                @endif
            </x-panel>

            <x-panel :title="__('Pedigree')">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-field border border-base-300 p-3">
                        <p class="text-label text-base-content/45">{{ __('Dam') }}</p>
                        <a href="{{ route('animals.show', $birth->dam) }}" class="link link-hover text-sm font-medium text-primary">
                            {{ $birth->dam->animal_code }}
                        </a>
                    </div>
                    <div class="rounded-field border border-base-300 p-3">
                        <p class="text-label text-base-content/45">{{ __('Sire') }}</p>
                        @if ($birth->sire)
                            <a href="{{ route('animals.show', $birth->sire) }}" class="link link-hover text-sm font-medium text-primary">
                                {{ $birth->sire->animal_code }}
                            </a>
                        @else
                            <p class="text-sm text-base-content/45">{{ __('Unknown') }}</p>
                        @endif
                    </div>
                    <div class="rounded-field border border-base-300 p-3">
                        <p class="text-label text-base-content/45">{{ __('Calf') }}</p>
                        @if ($birth->animal)
                            <a href="{{ route('animals.show', $birth->animal) }}" class="link link-hover text-sm font-medium text-primary">
                                {{ $birth->animal->animal_code }}
                            </a>
                        @else
                            <p class="text-sm text-base-content/45">{{ __('No live calf') }}</p>
                        @endif
                    </div>
                </div>
            </x-panel>
        </div>

        <x-panel :title="__('Effect on the dam')">
            <p class="text-xs leading-relaxed text-base-content/65">
                {{ __('After the birth the dam’s breeding status returns to open and her production status becomes lactating if it was dry.') }}
            </p>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Breeding') }}</dt>
                    <dd><x-badge :label="$birth->dam->breeding_status->label()" :class="$birth->dam->breeding_status->badgeClass()" /></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Production') }}</dt>
                    <dd><x-badge :label="$birth->dam->production_status->label()" :class="$birth->dam->production_status->badgeClass()" /></dd>
                </div>
            </dl>
        </x-panel>
    </div>
@endsection