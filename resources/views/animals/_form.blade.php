@php
    $editing = $animal->exists;
@endphp
@extends('layouts.app', ['title' => $editing ? __('Edit animal') : __('Add animal')])

@section('content')
    <x-page-header :title="$editing ? __('Edit :code', ['code' => $animal->animal_code]) : __('Add animal')"
                   :subtitle="$editing
                        ? __('Update the animal master record. Transaction history is not changed.')
                        : __('Complete the identity, statuses and where the animal is kept.')"
                   :back="route('animals.index')" />

    <form method="POST" action="{{ $editing ? route('animals.update', $animal) : route('animals.store') }}"
          class="grid gap-4 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Identity')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input name="animal_code" :label="__('Animal code')" :value="$animal->animal_code" required
                                  placeholder="T-00001" maxlength="30" />
                    <x-form.input name="name" :label="__('Name / alias')" :value="$animal->name"
                                  :placeholder="__('Optional')" maxlength="100" />
                    <x-form.select name="farm_id" :label="__('Farm')" :options="$farms" :selected="$animal->farm_id" required />
                    <x-form.select name="breed_id" :label="__('Breed')" :options="$breeds" :selected="$animal->breed_id" required />
                    <x-form.select name="sex" :label="__('Sex')" :options="$sexOptions" :selected="$animal->sex?->value" required />
                    <x-form.input name="date_of_birth" :label="__('Date of birth')" type="date" :value="$animal->date_of_birth?->toDateString()" />
                </div>
            </x-panel>

            <x-panel :title="__('Statuses')"
                     :description="__('Presence, production and breeding are tracked separately, so they can be active at the same time.')">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.select name="status" :label="__('Presence status')" :options="$statusOptions" :selected="$animal->status?->value" required />
                    <x-form.select name="production_status" :label="__('Production status')" :options="$productionOptions"
                                   :selected="$animal->production_status?->value" required />
                    <x-form.select name="breeding_status" :label="__('Breeding status')" :options="$breedingOptions"
                                   :selected="$animal->breeding_status?->value" required />
                </div>
                <p class="mt-3 text-xs text-base-content/50">
                    {{ __('For a sale, death or transfer use the “Remove from herd” action so the value and reason are recorded in the audit trail.') }}
                </p>
            </x-panel>

            <x-panel :title="__('Acquisition')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="acquisition_type" :label="__('Acquisition type')" :options="$acquisitionOptions"
                                   :selected="$animal->acquisition_type?->value" />
                    <x-form.input name="acquisition_date" :label="__('Acquisition date')" type="date"
                                  :value="$animal->acquisition_date?->toDateString()" />
                    <x-form.money name="acquisition_cost" :label="__('Acquisition value')" :value="$animal->acquisition_cost ?? 0" />
                    <x-form.input name="source_name" :label="__('Source / supplier')" :value="$animal->source_name"
                                  :placeholder="__('Supplier name or origin')" />
                </div>
            </x-panel>

            <x-panel :title="__('Notes')">
                <x-form.textarea name="notes" :label="__('Notes')" :value="$animal->notes" rows="3" />
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel :title="__('Location')">
                <x-form.select name="barn_id" :label="__('Barn')" :options="$barns" :selected="$animal->barn_id" />
                <p class="mt-2 text-xs text-base-content/50">
                    {{ __('Moving an animal between barns can be done from the detail page.') }}
                </p>
            </x-panel>

            <x-panel :title="__('Pedigree')" :description="__('Link the dam and sire to build the genealogy.')">
                <div class="grid gap-4">
                    <x-form.select name="dam_id" :label="__('Dam (mother)')" :options="$parents" :selected="$animal->dam_id" />
                    <x-form.select name="sire_id" :label="__('Sire (father)')" :options="$parents" :selected="$animal->sire_id" />
                </div>
            </x-panel>

            <x-panel>
                <x-form.actions bare>
                    <x-form.submit>{{ $editing ? __('Save changes') : __('Save animal') }}</x-form.submit>
                    <a href="{{ route('animals.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
                </x-form.actions>
            </x-panel>
        </div>
    </form>
@endsection