@extends('layouts.app', ['title' => __('Record pregnancy check')])

@section('content')
    <x-page-header :title="__('Record pregnancy check')"
                   :subtitle="__('The expected birth date is calculated from a 283-day gestation when it is left blank.')"
                   :back="route('pregnancy-checks.index')" />

    <form method="POST" action="{{ route('pregnancy-checks.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Check details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.animal-select name="animal_id" :label="__('Female')" :options="$animals"
                               :selected="old('animal_id', $check->animal_id ?? $breedingRecord?->animal_id ?? $breedingRecord?->animal?->id)"
                               required class="sm:col-span-2" />
                <x-form.select name="breeding_record_id" :label="__('Breeding record')" :options="$breedingRecords"
                               :selected="old('breeding_record_id', $breedingRecord?->id)"
                               :placeholder="__('— Without a breeding record —')" />
                <x-form.input name="check_date" :label="__('Check date')" type="date" required
                              :value="$check->check_date?->toDateString() ?? now()->toDateString()" />
                <x-form.select name="method" :label="__('Method')" :options="$methods" :selected="$check->method?->value" required />
                <x-form.select name="result" :label="__('Result')" :options="$results" :selected="$check->result?->value" required />
                <x-form.input name="estimated_gestation_days" :label="__('Gestation age (days)')" type="number" min="1" max="320"
                              :value="$check->estimated_gestation_days" placeholder="For example: 90" />
                <x-form.input name="expected_due_date" :label="__('Expected birth date')" type="date" :value="$check->expected_due_date?->toDateString()"
                              :hint="__('Leave empty to calculate it automatically from the breeding record.')" />
                <x-form.input name="fetus_count" :label="__('Estimated foetuses')" type="number" min="1" max="5" :value="$check->fetus_count" />
                <x-form.select name="veterinarian_id" :label="__('Examiner')" :options="$veterinarians" :selected="$check->veterinarian_id" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" :label="__('Notes')" :value="$check->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save check') }}</x-form.submit>
                <a href="{{ route('pregnancy-checks.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection
