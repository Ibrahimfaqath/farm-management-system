@php $editing = $breed->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Ras' : 'Tambah Ras'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$breed->name : 'Tambah Ras'" :back="route('breeds.index')" />

    <form method="POST" action="{{ $editing ? route('breeds.update', $breed) : route('breeds.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Data Ras">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" label="Kode Ras" :value="$breed->code" required maxlength="20" placeholder="HRF-01" />
                <x-form.input name="name" label="Nama Ras" :value="$breed->name" required maxlength="150" />
                <x-form.select name="species" label="Jenis Hewan" :options="$speciesOptions" :selected="$breed->species" required />
                <x-form.input name="origin" label="Asal" :value="$breed->origin" />
                <x-form.input name="average_milk_liters" label="Rata-rata Produksi Susu (L/hari)" type="number" step="0.1" min="0" :value="$breed->average_milk_liters" />
                <x-form.input name="average_lactation_days" label="Rata-rata Lama Laktasi (hari)" type="number" min="1" :value="$breed->average_lactation_days" />
                <x-form.input name="adult_weight_kg" label="Berat Dewasa (kg)" type="number" step="0.1" min="0" :value="$breed->adult_weight_kg" />
            </div>
            <div class="mt-4"><x-form.checkbox name="is_active" label="Ras aktif digunakan" :checked="$breed->is_active ?? true" /></div>
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$breed->notes" rows="3" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Ras' }}</button>
                <a href="{{ route('breeds.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
