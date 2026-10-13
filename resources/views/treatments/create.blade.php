@extends('layouts.app', ['title' => __('Record treatment')])

@section('content')
    <x-page-header :title="__('Record treatment')"
                   :subtitle="__('The withdrawal period decides when the milk may be consumed again.')"
                   :back="route('treatments.index')" />

    <form method="POST" action="{{ route('treatments.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Treatment details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="animal_id" :label="__('Animal')" :options="$animals"
                               :selected="old('animal_id', $treatment->animal_id ?? $animal?->id)" required class="sm:col-span-2" />
                <x-form.select name="health_record_id" :label="__('Health record (optional)')"
                               :options="$healthRecords->mapWithKeys(fn ($r) => [$r->id => $r->record_date->translatedFormat('d M Y').' · '.($r->diagnosis ?? $r->symptoms)])"
                               :placeholder="__('— No record —')" />
                <x-form.input name="medication_name" :label="__('Medication name')" :value="$treatment->medication_name" required
                              :placeholder="__('For example: oxytetracycline 20%')" list="med-names" />
                <datalist id="med-names">
                    <option value="Oxytetracycline 20%"></option>
                    <option value="Penicillin Procaine"></option>
                    <option value="Ivermectin"></option>
                    <option value="Ketoprofen"></option>
                    <option value="Vitamin B Complex"></option>
                </datalist>
                <x-form.input name="dosage" :label="__('Dosage')" :value="$treatment->dosage" :placeholder="__('For example: 20 ml IM')" />
                <x-form.input name="route" :label="__('Route')" :value="$treatment->route" placeholder="IM / SC / Oral / Topical" />
                <x-form.input name="frequency" :label="__('Frequency')" :value="$treatment->frequency" :placeholder="__('For example: twice daily')" />
            </div>
        </x-panel>

        <x-panel :title="__('Period and cost')">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.input name="treatment_date" :label="__('Start date')" type="date" required
                              :value="$treatment->treatment_date?->toDateString() ?? now()->toDateString()" />
                <x-form.input name="end_date" :label="__('End date')" type="date" :value="$treatment->end_date?->toDateString()" />
                <x-form.input name="withdrawal_period_days" :label="__('Withdrawal period (days)')" type="number" min="0"
                              :value="$treatment->withdrawal_period_days"
                              :hint="__('Milk is held for the whole period.')" />
                <x-form.money name="cost" :label="__('Cost')" :value="$treatment->cost ?? 0" />
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-form.select name="veterinarian_id" :label="__('Veterinarian / officer')" :options="$veterinarians" :selected="$treatment->veterinarian_id" />
                <x-form.textarea name="notes" :label="__('Notes')" :value="$treatment->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save treatment') }}</x-form.submit>
                <a href="{{ route('treatments.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection
