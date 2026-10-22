@php
    $editing = $record->exists;
    $selectedAnimal = old('animal_id', $record->animal_id ?? $animal?->id);
@endphp
@extends('layouts.app', ['title' => $editing ? __('Edit health record') : __('Record health')])

@section('content')
    <x-page-header :title="$editing ? __('Edit health record') : __('Record health')"
                   :subtitle="__('A diagnosis is entered by a veterinarian. The system never infers a disease from production data.')"
                   :back="$editing ? route('health-records.show', $record) : route('health-records.index')" />

    <form method="POST" action="{{ $editing ? route('health-records.update', $record) : route('health-records.store') }}"
          class="max-w-4xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel :title="__('Record context')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.animal-select name="animal_id" :label="__('Livestock')" :options="$animals" :selected="$selectedAnimal" required
                               class="sm:col-span-2" />
                <x-form.input name="record_date" :label="__('Record date')" type="date" required
                              :value="$record->record_date?->toDateString() ?? now()->toDateString()" />
                <x-form.select name="severity" :label="__('Severity')" :options="$severities" :selected="$record->severity?->value" />
            </div>
        </x-panel>

        <x-panel :title="__('Record details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="record_type" :label="__('Record type')" :options="$recordTypes" :selected="$record->record_type?->value" required />
                <x-form.select name="status" :label="__('Status')" :options="$statuses" :selected="$record->status?->value" required />
            </div>
            <div class="mt-4 space-y-4">
                <x-form.textarea name="symptoms" :label="__('Symptoms / observations')" :value="$record->symptoms" rows="3"
                                 :placeholder="__('For example: reduced appetite, nasal discharge')" />
                <x-form.textarea name="diagnosis" :label="__('Diagnosis (by a veterinarian)')" :value="$record->diagnosis" rows="2"
                                 :placeholder="__('Filled in by the attending veterinarian or authorised officer')" />
                <x-form.textarea name="notes" :label="__('Notes')" :value="$record->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel :title="__('Follow-up')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.input name="follow_up_date" :label="__('Follow-up date')" type="date" :value="$record->follow_up_date?->toDateString()" />
                <x-form.input name="resolved_date" :label="__('Resolved on')" type="date" :value="$record->resolved_date?->toDateString()" />
                <x-form.money name="cost" :label="__('Cost')" :value="$record->cost ?? 0" />
                <x-form.select name="veterinarian_id" :label="__('Veterinarian / officer')" :options="$veterinarians"
                               :selected="$record->veterinarian_id" />
            </div>
        </x-panel>

        <x-form.actions boxed>
            <x-form.submit>{{ $editing ? __('Save changes') : __('Save record') }}</x-form.submit>
            <a href="{{ $editing ? route('health-records.show', $record) : route('health-records.index') }}"
               class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
        </x-form.actions>
    </form>
@endsection