@extends('layouts.app', ['title' => __('Record breeding')])

@section('content')
    <x-page-header :title="__('Record breeding')"
                   :subtitle="__('A breeding record moves the animal to “pending check”. Pregnancy can only be established by a pregnancy check.')"
                   :back="route('breeding-records.index')" />

    <form method="POST" action="{{ route('breeding-records.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Breeding details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.animal-select name="animal_id" :label="__('Female')" :options="$females"
                               :selected="old('animal_id', $record->animal_id ?? $animal?->id)" required class="sm:col-span-2" />
                <x-form.select name="record_type" :label="__('Record type')" :options="$recordTypes" :selected="$record->record_type?->value" required />
                <x-form.input name="breeding_date" :label="__('Date')" type="date" required
                              :value="$record->breeding_date?->toDateString() ?? now()->toDateString()" />
                <x-form.select name="bull_id" :label="__('Sire')" :options="$bulls" :selected="$record->bull_id"
                               :placeholder="__('— Local sire / semen code —')" />
                <x-form.input name="semen_code" :label="__('Semen code')" :value="$record->semen_code" placeholder="For example: BRF-01" />
                <x-form.input name="semen_batch" :label="__('Semen batch number')" :value="$record->semen_batch" />
                <x-form.input name="inseminator" :label="__('Inseminator')" :value="$record->inseminator" />
                <x-form.input name="expected_next_heat_date" :label="__('Expected next heat')" type="date"
                              :value="$record->expected_next_heat_date?->toDateString()" />
            </div>
        </x-panel>

        <x-panel :title="__('Observations')">
            <x-form.textarea name="heat_signs" :label="__('Observed signs')" :value="$record->heat_signs" rows="2"
                             :placeholder="__('For example: restlessness, clear discharge, frequent mounting')" />
            <div class="mt-3">
                <x-form.textarea name="notes" :label="__('Notes')" :value="$record->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save breeding record') }}</x-form.submit>
                <a href="{{ route('breeding-records.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection
