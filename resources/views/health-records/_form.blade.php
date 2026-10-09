@php
    $editing = $record->exists;
    $selectedAnimal = old('animal_id', $record->animal_id ?? $animal?->id);
@endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Rekam Kesehatan' : 'Catat Rekam Kesehatan'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah Rekam Kesehatan' : 'Catat Rekam Kesehatan'"
                   subtitle="Diagnosis hanya diisi oleh tenaga medis. Sistem tidak menyimpulkan penyakit dari data produksi."
                   :back="$editing ? route('health-records.show', $record) : route('health-records.index')" />

    <form method="POST" action="{{ $editing ? route('health-records.update', $record) : route('health-records.store') }}"
          class="max-w-4xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Konteks Rekam">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.select name="animal_id" label="Ternak" :options="$animals" :selected="$selectedAnimal" required class="sm:col-span-2" />
                <x-form.input name="record_date" label="Tanggal Rekam" type="date" :value="$record->record_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.select name="severity" label="Tingkat" :options="$severities" :selected="$record->severity?->value" />
            </div>
        </x-panel>

        <x-panel title="Isi Rekam">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="record_type" label="Jenis Rekam" :options="$recordTypes" :selected="$record->record_type?->value" required />
                <x-form.select name="status" label="Status" :options="$statuses" :selected="$record->status?->value" required />
            </div>
            <div class="mt-4 space-y-4">
                <x-form.textarea name="symptoms" label="Gejala / Observasi" :value="$record->symptoms" rows="3"
                                 placeholder="Contoh: nafsu makan turun, keluar leleran dari hidung" />
                <x-form.textarea name="diagnosis" label="Diagnosis (tenaga medis)" :value="$record->diagnosis" rows="2"
                                 placeholder="Diisi oleh dokter hewan atau petugas berwenang" />
                <x-form.textarea name="notes" label="Catatan" :value="$record->notes" rows="2" />
            </div>
        </x-panel>

        <x-panel title="Tindak Lanjut">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-form.input name="follow_up_date" label="Tindak Lanjut" type="date" :value="$record->follow_up_date?->toDateString()" />
                <x-form.input name="resolved_date" label="Tanggal Selesai" type="date" :value="$record->resolved_date?->toDateString()" />
                <x-form.money name="cost" label="Biaya" :value="$record->cost ?? 0" />
                <x-form.select name="veterinarian_id" label="Dokter / Petugas" :options="$veterinarians" :selected="$record->veterinarian_id" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Rekam' }}</button>
                <a href="{{ $editing ? route('health-records.show', $record) : route('health-records.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
