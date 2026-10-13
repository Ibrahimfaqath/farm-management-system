@extends('layouts.app', ['title' => __('Record birth')])

@section('content')
    <x-page-header :title="__('Record birth')"
                   :subtitle="__('A live calf can be registered as a new animal with its pedigree already linked.')"
                   :back="route('birth-records.index')" />

    <form method="POST" action="{{ route('birth-records.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel :title="__('Birth details')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="dam_id" :label="__('Dam')" :options="$dams"
                               :selected="old('dam_id', $birth->dam_id ?? $dam?->id)" required class="sm:col-span-2" />
                <x-form.select name="sire_id" :label="__('Sire')" :options="$sires" :selected="$birth->sire_id"
                               :placeholder="__('— Unknown —')" />
                <x-form.select name="pregnancy_check_id" :label="__('Pregnancy check')" :options="$pregnancyChecks"
                               :selected="old('pregnancy_check_id', $pregnancyCheck?->id)"
                               :placeholder="__('— Without a check —')" />
                <x-form.input name="birth_date" :label="__('Birth date')" type="date" required
                              :value="$birth->birth_date?->toDateString() ?? now()->toDateString()" />
                <x-form.input name="birth_time" :label="__('Birth time')" type="time" />
                <x-form.select name="birth_type" :label="__('Birth type')" :options="$birthTypes" :selected="$birth->birth_type?->value" required />
                <x-form.input name="birth_weight_kg" :label="__('Birth weight (kg)')" type="number" step="0.1" min="1" :value="$birth->birth_weight_kg" />
                <x-form.select name="calf_sex" :label="__('Calf sex')" :options="$sexOptions"
                               :selected="old('calf_sex', $birth->calf_sex === 0 ? 'female' : 'male')" />
                <x-form.input name="colostrum_intake" :label="__('Colostrum given')" :value="$birth->colostrum_intake"
                              :placeholder="__('For example: first 2 hours, 10% of body weight')" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="assistance_notes" :label="__('Assistance notes')" :value="$birth->assistance_notes" rows="2" />
            </div>
        </x-panel>

        <x-panel :title="__('Calf registration')"
                 :description="__('When ticked, the system creates the calf as a new animal and links it to both parents.')">
            <x-form.checkbox name="register_calf" value="1" :label="__('Register the calf as a new animal')" />
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <x-form.input name="calf_code" :label="__('Calf code')" placeholder="For example: T-00031" />
                <x-form.input name="calf_name" :label="__('Calf name (optional)')" />
            </div>
            @error('calf_code')<p class="mt-1 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('calf_sex')<p class="mt-1 text-xs text-tone-error">{{ $message }}</p>@enderror
            @error('register_calf')<p class="mt-1 text-xs text-tone-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel>
            <x-form.actions bare>
                <x-form.submit>{{ __('Save birth') }}</x-form.submit>
                <a href="{{ route('birth-records.index') }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            </x-form.actions>
        </x-panel>
    </form>
@endsection
