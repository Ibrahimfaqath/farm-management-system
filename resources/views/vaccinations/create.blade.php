@php $editing = $vaccination->exists; @endphp
@extends('layouts.app', ['title' => $editing ? __('Edit vaccination') : __('Record vaccination')])

@section('content')
    <x-page-header :title="$editing ? __('Edit vaccination record') : __('Record vaccination')"
                   :back="route('vaccinations.index')" />

    <form method="POST" action="{{ route('vaccinations.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Vaccination details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.animal-select name="animal_id" :label="__('Animal')" :options="$animals"
                               :selected="old('animal_id', $vaccination->animal_id ?? $animal?->id)" required class="sm:col-span-2" />
                <x-form.input name="vaccine_name" :label="__('Vaccine name')" :value="$vaccination->vaccine_name" required
                              :placeholder="__('For example: brucellosis')" list="vaccine-names" />
                <datalist id="vaccine-names">
                    <option value="Brucellosis"></option>
                    <option value="FMD"></option>
                    <option value="Haemorrhagic Septicaemia"></option>
                    <option value="Anthrax"></option>
                    <option value="BVD"></option>
                    <option value="Newcastle (AI)"></option>
                </datalist>
                <x-form.input name="batch_number" :label="__('Batch number')" :value="$vaccination->batch_number" />
                <x-form.input name="administered_date" :label="__('Date administered')" type="date" required
                              :value="$vaccination->administered_date?->toDateString() ?? now()->toDateString()" />
                <x-form.input name="next_due_date" :label="__('Next due date')" type="date" :value="$vaccination->next_due_date?->toDateString()" />
                <x-form.input name="dose" :label="__('Dose')" :value="$vaccination->dose" :placeholder="__('For example: 2 ml IM')" />
                <x-form.input name="route" :label="__('Route')" :value="$vaccination->route" placeholder="IM / SC / Oral" />
                <x-form.money name="cost" :label="__('Cost')" :value="$vaccination->cost ?? 0" />
                <x-form.select name="veterinarian_id" :label="__('Veterinarian / officer')" :options="$veterinarians" :selected="$vaccination->veterinarian_id" />
                <x-form.input name="administered_by" :label="__('Administered by')" :value="$vaccination->administered_by" />
            </div>
        </x-panel>

        <x-panel :title="__('Notes')">
            <x-form.textarea name="notes" :label="__('Notes')" :value="$vaccination->notes" rows="2" />
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save vaccination') }}</x-form.submit>
                <a href="{{ route('vaccinations.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection
