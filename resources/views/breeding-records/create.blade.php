@extends('layouts.app', ['title' => 'Catat Peminahan'])

@section('content')
    <x-page-header title="Catat Peminahan"
                   subtitle="Peminahan membuat status menjadi menunggu pemeriksaan. Kehamilan hanya dapat ditetapkan lewat pemeriksaan kehamilan."
                   :back="route('breeding-records.index')" />

    <form method="POST" action="{{ route('breeding-records.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Data Peminahan">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="animal_id" label="Ternak Betina" :options="$females"
                               :selected="old('animal_id', $record->animal_id ?? $animal?->id)" required class="sm:col-span-2" />
                <x-form.select name="record_type" label="Jenis Catatan" :options="$recordTypes" :selected="$record->record_type?->value" required />
                <x-form.input name="breeding_date" label="Tanggal" type="date"
                              :value="$record->breeding_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.select name="bull_id" label="Pejantan" :options="$bulls" :selected="$record->bull_id"
                               placeholder="— Pejantan lokal / kode semen —" />
                <x-form.input name="semen_code" label="Kode Semen" :value="$record->semen_code" placeholder="Contoh: BRF-01" />
                <x-form.input name="semen_batch" label="Nomor Batch Semen" :value="$record->semen_batch" />
                <x-form.input name="inseminator" label="Inseminator" :value="$record->inseminator" />
                <x-form.input name="expected_next_heat_date" label="Perkiraan Birahi Berikutnya" type="date"
                              :value="$record->expected_next_heat_date?->toDateString()" />
            </div>
        </x-panel>

        <x-panel title="Observasi">
            <x-form.textarea name="heat_signs" label="Gejala yang Diamati" :value="$record->heat_signs" rows="2"
                             placeholder="Contoh: betina gelisah, lendir bening keluar, sering melengking" />
            <div class="mt-3">
                <x-form.textarea name="notes" label="Catatan" :value="$record->notes" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Peminahan</button>
                <a href="{{ route('breeding-records.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
