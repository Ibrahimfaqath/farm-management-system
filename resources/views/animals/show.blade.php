@php
    $tabs = [
        'riwayat' => __('Activity trail'),
        'kesehatan' => __('Health'),
        'breeding' => __('Breeding'),
        'produksi' => __('Production'),
    ];
    $current = request()->query('tab', 'riwayat');
@endphp
@extends('layouts.app', ['title' => $animal->animal_code])

@section('content')
    <x-page-header :title="$animal->animal_code.($animal->name ? ' · '.$animal->name : '')"
                   :subtitle="$animal->breed?->name.' · '.$animal->sex->label().' · '.$animal->age_label"
                   :back="route('animals.index')"
                   :breadcrumbs="[
                       ['label' => __('Livestock'), 'url' => route('animals.index')],
                       ['label' => $animal->animal_code],
                   ]">
        <x-slot:actions>
            @if ($animal->status->isOpen() && auth()->user()->hasAbility('animal.exit'))
                <button type="button" class="btn btn-outline btn-error btn-sm" data-modal-open="exit-modal">
                    {{ __('Remove from herd') }}
                </button>
            @endif
            @if (! $animal->status->isOpen() && auth()->user()->hasAbility('animal.manage'))
                <button type="button" class="btn btn-outline btn-warning btn-sm" data-modal-open="reopen-modal">
                    {{ __('Return to herd') }}
                </button>
            @endif
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('animals.edit', $animal) }}" class="btn btn-primary btn-sm">{{ __('Edit animal') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @unless ($animal->status->isOpen())
        <x-alert-row tone="warning" class="mb-4"
                     :title="__('This animal is :status', ['status' => $animal->status->label()])"
                     :description="__('History stays available as evidence. It left the herd on :date.', ['date' => $animal->exit_date?->translatedFormat('d M Y')])" />
    @endunless

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat :label="__('Status')" :value="$animal->status->label()"
               :tone="$animal->status->isOpen() ? 'success' : 'warning'" />
        <x-stat :label="__('Production')" :value="$animal->production_status->label()" />
        <x-stat :label="__('Breeding')" :value="$animal->breeding_status->label()" />
        <x-stat :label="__('Barn')" :value="$animal->barn?->name ?? '—'" />
        <x-stat :label="__('Milk, 30 days')"
               :value="($milkSummary->total ?? 0) > 0 ? number_format((float) $milkSummary->total, 1).' L' : '—'"
               :hint="($milkSummary->records ?? 0) > 0
                    ? __('Average :avg L per record', ['avg' => number_format((float) $milkSummary->average, 1)])
                    : __('No records yet')" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <div role="tablist" class="tabs tabs-box tabs-sm w-fit bg-base-100">
                @foreach ($tabs as $key => $label)
                    <a role="tab" href="?tab={{ $key }}" class="tab" @selected($current === $key)>
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if ($current === 'riwayat')
                @include('animals.tabs.history')
            @elseif ($current === 'kesehatan')
                @include('animals.tabs.health')
            @elseif ($current === 'breeding')
                @include('animals.tabs.breeding')
            @else
                @include('animals.tabs.production')
            @endif
        </div>

        <div class="space-y-4">
            <x-panel :title="__('Identity')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Breed') }}</dt>
                        <dd class="text-right">{{ $animal->breed?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Farm') }}</dt>
                        <dd class="text-right">{{ $animal->farm?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Date of birth') }}</dt>
                        <dd class="text-right">{{ $animal->date_of_birth?->translatedFormat('d M Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Acquisition') }}</dt>
                        <dd class="text-right">{{ $animal->acquisition_type?->label() ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Acquisition value') }}</dt>
                        <dd class="text-right"><x-money :amount="$animal->acquisition_cost ?? 0" /></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Health') }}</dt>
                        <dd class="text-right">{{ $animal->healthStatusLabel() }}</dd>
                    </div>
                    @if ($animal->exit_date)
                        <div class="flex justify-between gap-3">
                            <dt class="text-base-content/55">{{ __('Exit date') }}</dt>
                            <dd class="text-right">{{ $animal->exit_date->translatedFormat('d M Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-base-content/55">{{ __('Exit value') }}</dt>
                            <dd class="text-right"><x-money :amount="$animal->exit_value ?? 0" /></dd>
                        </div>
                    @endif
                </dl>

                @if ($animal->notes)
                    <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $animal->notes }}</p>
                @endif
                @if ($animal->exit_notes)
                    <p class="alert alert-soft alert-warning mt-2 px-3 py-2.5 text-xs leading-relaxed">{{ $animal->exit_notes }}</p>
                @endif
            </x-panel>

            <x-panel :title="__('Pedigree')">
                <div class="grid gap-3">
                    <div class="rounded-field border border-base-300 p-2.5">
                        <p class="text-label text-base-content/45">{{ __('Dam (mother)') }}</p>
                        @if ($animal->dam)
                            <a href="{{ route('animals.show', $animal->dam) }}" class="link link-hover text-sm font-medium text-primary">
                                {{ $animal->dam->animal_code }}
                            </a>
                            <p class="truncate text-xs text-base-content/55">{{ $animal->dam->name ?? __('Unnamed') }}</p>
                        @else
                            <p class="text-sm text-base-content/45">{{ __('Unknown') }}</p>
                        @endif
                    </div>

                    <div class="rounded-field border border-base-300 p-2.5">
                        <p class="text-label text-base-content/45">{{ __('Sire (father)') }}</p>
                        @if ($animal->sire)
                            <a href="{{ route('animals.show', $animal->sire) }}" class="link link-hover text-sm font-medium text-primary">
                                {{ $animal->sire->animal_code }}
                            </a>
                            <p class="truncate text-xs text-base-content/55">{{ $animal->sire->name ?? __('Unnamed') }}</p>
                        @else
                            <p class="text-sm text-base-content/45">{{ __('Unknown') }}</p>
                        @endif
                    </div>

                    @if ($animal->calves->isNotEmpty())
                        <div class="rounded-field border border-base-300 p-2.5">
                            <p class="text-label text-base-content/45">
                                {{ trans_choice(':count calf|Calves (:count)', $animal->calves->count(), ['count' => $animal->calves->count()]) }}
                            </p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @foreach ($animal->calves as $calf)
                                    <a href="{{ route('animals.show', $calf) }}" class="badge badge-sm border border-base-300 bg-base-100 hover:border-primary">
                                        {{ $calf->animal_code }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-panel>
        </div>
    </div>

    @if ($animal->status->isOpen() && auth()->user()->hasAbility('animal.exit'))
        <x-modal id="exit-modal" :title="__('Remove :code from the herd', ['code' => $animal->animal_code])"
                 :description="__('The animal is not deleted. Health, breeding, production and financial history stay accessible.')">
            <form method="POST" action="{{ route('animals.exit', $animal) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.select name="exit_type" :label="__('Exit type')" required
                                   :options="['sold' => __('Sold'), 'deceased' => __('Died'), 'transferred' => __('Transferred')]" />
                    <x-form.input name="exit_date" :label="__('Date')" type="date" required :value="now()->toDateString()" />
                    <x-form.money name="exit_value" :label="__('Transaction value')" :value="0" />
                    <x-form.input name="exit_reference" :label="__('Reference number')" :placeholder="__('Receipt or note number')" />
                </div>
                <x-form.input name="reason" :label="__('Reason')" required
                              :placeholder="__('For example: sold to CV Sumber Susu')" />
                <x-form.textarea name="exit_notes" :label="__('Additional notes')" rows="2" />
                <x-form.actions>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Cancel') }}</button>
                    <x-form.submit variant="error">{{ __('Confirm removal') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif

    @if (! $animal->status->isOpen() && auth()->user()->hasAbility('animal.manage'))
        <x-modal id="reopen-modal" :title="__('Return :code to the herd?', ['code' => $animal->animal_code])"
                 :description="__('The animal becomes active again and its exit record is cleared. History is kept.')">
            <form method="POST" action="{{ route('animals.reopen', $animal) }}" class="mt-4">
                @csrf
                <x-form.actions bare>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Cancel') }}</button>
                    <x-form.submit variant="warning">{{ __('Return to herd') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection