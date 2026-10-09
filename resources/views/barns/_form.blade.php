@php $editing = $barn->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Kandang' : 'Tambah Kandang'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$barn->name : 'Tambah Kandang'" :back="route('barns.index')" />

    <form method="POST" action="{{ $editing ? route('barns.update', $barn) : route('barns.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Data Kandang">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="farm_id" label="Peternakan" :options="$farms" :selected="$barn->farm_id" required />
                <x-form.input name="code" label="Kode Kandang" :value="$barn->code" required maxlength="20" placeholder="KAND-A" />
                <x-form.input name="name" label="Nama Kandang" :value="$barn->name" required maxlength="150" />
                <x-form.select name="type" label="Jenis Kandang" :options="$types" :selected="$barn->type?->value" required />
                <x-form.input name="capacity" label="Kapasitas (ekor)" type="number" min="1" :value="$barn->capacity" />
                <x-form.input name="area_m2" label="Luas (m²)" type="number" step="0.01" min="0" :value="$barn->area_m2" />
            </div>
            <div class="mt-4"><x-form.checkbox name="is_active" label="Kandang aktif digunakan" :checked="$barn->is_active ?? true" /></div>
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$barn->notes" rows="3" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Kandang' }}</button>
                <a href="{{ route('barns.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
