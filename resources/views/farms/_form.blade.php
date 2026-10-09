@php $editing = $farm->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Peternakan' : 'Tambah Peternakan'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$farm->name : 'Tambah Peternakan'" :back="route('farms.index')" />

    <form method="POST" action="{{ $editing ? route('farms.update', $farm) : route('farms.store') }}" class="max-w-4xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Identitas">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.input name="code" label="Kode" :value="$farm->code" required maxlength="20" placeholder="FRM-001" />
                <x-form.input name="name" label="Nama Peternakan" :value="$farm->name" required maxlength="150" />
                <x-form.input name="manager_name" label="Nama Pengelola" :value="$farm->manager_name" />
                <x-form.input name="phone" label="Telepon" :value="$farm->phone" />
                <x-form.input name="area_hectares" label="Luas Lahan (ha)" type="number" step="0.01" min="0" :value="$farm->area_hectares" />
                <x-form.input name="established_date" label="Tanggal Berdiri" type="date" :value="$farm->established_date?->toDateString()" />
            </div>
        </x-panel>

        <x-panel title="Alamat">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.input name="address" label="Alamat Jalan" :value="$farm->address" />
                <x-form.input name="village" label="Desa / Kelurahan" :value="$farm->village" />
                <x-form.input name="district" label="Kecamatan" :value="$farm->district" />
                <x-form.input name="regency" label="Kabupaten / Kota" :value="$farm->regency" />
                <x-form.input name="province" label="Provinsi" :value="$farm->province" />
                <x-form.input name="postal_code" label="Kode Pos" :value="$farm->postal_code" maxlength="10" />
            </div>
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan Umum" :value="$farm->notes" rows="3" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Peternakan' }}</button>
                <a href="{{ route('farms.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
