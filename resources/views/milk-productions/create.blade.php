@extends('layouts.app', ['title' => 'Catat Produksi Susu'])

@section('content')
    <x-page-header title="Catat Produksi Susu"
                   subtitle="Satu catatan hanya boleh untuk satu ekor ATAU satu kelompok kandang agar data tidak terhitung ganda."
                   :back="route('milk-productions.index')" />

    <form method="POST" action="{{ route('milk-productions.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Sumber Produksi">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="farm_id" label="Peternakan" :options="$farms"
                               :selected="old('farm_id', $animal?->farm_id ?? $farms->keys()->first())" required />
                <x-form.select name="animal_id" label="Ternak (individu)"
                               :options="$animals" :selected="old('animal_id', $animal?->id)"
                               placeholder="— Catatan level kandang —" />
                <x-form.select name="barn_id" label="Kandang (kelompok)" :options="$barns" :selected="old('barn_id', $animal?->barn_id)"
                               placeholder="— Catatan per ekor —" />
                <x-form.input name="production_date" label="Tanggal" type="date" :value="now()->toDateString()" required />
                <x-form.select name="session" label="Sesi" :options="$sessions" :selected="old('session', 'mixed')" required />
            </div>
            @error('animal_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('barn_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('session')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Volume dan Mutu">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.input name="liters" label="Volume (Liter)" type="number" step="0.1" min="0" max="200" required />
                <x-form.input name="fat_percent" label="Kadar Lemak (%)" type="number" step="0.01" min="0" max="10" />
                <x-form.input name="protein_percent" label="Kadar Protein (%)" type="number" step="0.01" min="0" max="15" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" label="Catatan" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Produksi</button>
                <a href="{{ route('milk-productions.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
