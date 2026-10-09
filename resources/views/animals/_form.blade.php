@php
    $editing = $animal->exists;
@endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Ternak' : 'Tambah Ternak'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$animal->animal_code : 'Tambah Ternak'"
                   :subtitle="$editing ? 'Perbarui data master hewan. Riwayat transaksi tidak berubah.' : 'Lengkapi identitas, status, dan posisi hewan di peternakan.'"
                   :back="route('animals.index')" />

    <form method="POST" action="{{ $editing ? route('animals.update', $animal) : route('animals.store') }}"
          class="grid gap-4 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Identitas">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input name="animal_code" label="Kode Hewan" :value="$animal->animal_code" required
                                  placeholder="T-00001" maxlength="30" />
                    <x-form.input name="name" label="Nama / Alias" :value="$animal->name" placeholder="Opsional" maxlength="100" />
                    <x-form.select name="farm_id" label="Peternakan" :options="$farms" :selected="$animal->farm_id" required />
                    <x-form.select name="breed_id" label="Ras" :options="$breeds" :selected="$animal->breed_id" required />
                    <x-form.select name="sex" label="Jenis Kelamin" :options="$sexOptions" :selected="$animal->sex?->value" required />
                    <x-form.input name="date_of_birth" label="Tanggal Lahir" type="date" :value="$animal->date_of_birth?->toDateString()" />
                </div>
            </x-panel>

            <x-panel title="Status"
                     description="Status keberadaan, produksi, dan breeding berdiri sendiri sehingga dapat aktif bersamaan.">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.select name="status" label="Status Keberadaan" :options="$statusOptions" :selected="$animal->status?->value" required />
                    <x-form.select name="production_status" label="Status Produksi" :options="$productionOptions" :selected="$animal->production_status?->value" required />
                    <x-form.select name="breeding_status" label="Status Breeding" :options="$breedingOptions" :selected="$animal->breeding_status?->value" required />
                </div>
                <p class="mt-3 text-xs text-base-content/50">
                    Untuk penjualan, kematian, atau pemindahan gunakan tindakan "Keluarkan Ternak" agar nilai dan alasan tercatat pada jejak audit.
                </p>
            </x-panel>

            <x-panel title="Perolehan">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="acquisition_type" label="Jenis Perolehan" :options="$acquisitionOptions" :selected="$animal->acquisition_type?->value" />
                    <x-form.input name="acquisition_date" label="Tanggal Perolehan" type="date" :value="$animal->acquisition_date?->toDateString()" />
                    <x-form.money name="acquisition_cost" label="Nilai Perolehan" :value="$animal->acquisition_cost ?? 0" />
                    <x-form.input name="source_name" label="Sumber / Pemasok" :value="$animal->source_name" placeholder="Nama pemasok atau asal animals" />
                </div>
            </x-panel>

            <x-panel title="Keterangan">
                <x-form.textarea name="notes" label="Catatan" :value="$animal->notes" rows="3" />
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel title="Lokasi">
                <x-form.select name="barn_id" label="Kandang" :options="$barns" :selected="$animal->barn_id" />
                <p class="mt-2 text-xs text-base-content/50">Perpindahan hewan antar kandang dapat dilakukan dari halaman detail.</p>
            </x-panel>

            <x-panel title="Silsilah" description="Hubungkan induk betina dan jantan untuk membangun genealogi.">
                <div class="grid gap-4">
                    <x-form.select name="dam_id" label="Induk Betina (Dam)" :options="$parents" :selected="$animal->dam_id" />
                    <x-form.select name="sire_id" label="Induk Jantan (Sire)" :options="$parents" :selected="$animal->sire_id" />
                </div>
            </x-panel>

            <div class="rounded-box border border-base-300 bg-base-100 p-4">
                <x-form.actions>
                    <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Ternak' }}</button>
                    <a href="{{ route('animals.index') }}" class="btn btn-ghost btn-sm">Batal</a>
                </x-form.actions>
            </div>
        </div>
    </form>
@endsection
