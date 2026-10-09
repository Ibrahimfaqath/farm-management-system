@extends('layouts.app', ['title' => 'Catat Pemeriksaan Kehamilan'])

@section('content')
    <x-page-header title="Catat Pemeriksaan Kehamilan"
                   subtitle="Perkiraan tanggal lahir dihitung dari masa kebuntingan 283 hari bila tidak diisi manual."
                   :back="route('pregnancy-checks.index')" />

    <form method="POST" action="{{ route('pregnancy-checks.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Data Pemeriksaan">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="animal_id" label="Ternak Betina" :options="$animals"
                               :selected="old('animal_id', $check->animal_id ?? $breedingRecord?->animal_id ?? $breedingRecord?->animal?->id)" required class="sm:col-span-2" />
                <x-form.select name="breeding_record_id" label="Catatan Peminahan" :options="$breedingRecords"
                               :selected="old('breeding_record_id', $breedingRecord?->id)" placeholder="— Tanpa catatan peminahan —" />
                <x-form.input name="check_date" label="Tanggal Pemeriksaan" type="date"
                              :value="$check->check_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.select name="method" label="Metode" :options="$methods" :selected="$check->method?->value" required />
                <x-form.select name="result" label="Hasil" :options="$results" :selected="$check->result?->value" required />
                <x-form.input name="estimated_gestation_days" label="Usia Kehamilan (hari)" type="number" min="1" max="320"
                              :value="$check->estimated_gestation_days" placeholder="Contoh: 90" />
                <x-form.input name="expected_due_date" label="Perkiraan Tanggal Lahir" type="date" :value="$check->expected_due_date?->toDateString()"
                              hint="Kosongkan untuk menghitung otomatis dari catatan peminahan." />
                <x-form.input name="fetus_count" label="Perkiraan Janin" type="number" min="1" max="5" :value="$check->fetus_count" />
                <x-form.select name="veterinarian_id" label="Pemeriksa" :options="$veterinarians" :selected="$check->veterinarian_id" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" label="Catatan" :value="$check->notes" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Pemeriksaan</button>
                <a href="{{ route('pregnancy-checks.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
