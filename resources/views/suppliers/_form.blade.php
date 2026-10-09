@php $editing = $supplier->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Pemasok' : 'Tambah Pemasok'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$supplier->name : 'Tambah Pemasok'" :back="route('suppliers.index')" />

    <form method="POST" action="{{ $editing ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
          class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Identitas">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" label="Kode Pemasok" :value="$supplier->code" required maxlength="30" placeholder="SUP-001" />
                <x-form.input name="name" label="Nama Pemasok" :value="$supplier->name" required maxlength="150" />
                <x-form.select name="category" label="Jenis Pemasok" :options="$categories" :selected="$supplier->category" required />
                <x-form.input name="tax_number" label="NPWP" :value="$supplier->tax_number" maxlength="40" />
            </div>
        </x-panel>

        <x-panel title="Kontak">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="contact_person" label="Nama Kontak" :value="$supplier->contact_person" />
                <x-form.input name="phone" label="Telepon" :value="$supplier->phone" />
                <x-form.input name="email" label="Email" type="email" :value="$supplier->email" />
                <x-form.input name="city" label="Kota" :value="$supplier->city" />
                <x-form.input name="address" label="Alamat" :value="$supplier->address" />
                <x-form.input name="payment_terms_days" label="Termin Pembayaran (hari)" type="number" min="0" max="180"
                              :value="$supplier->payment_terms_days ?? 0" />
            </div>
            <div class="mt-4"><x-form.checkbox name="is_active" label="Pemasok aktif" :checked="$supplier->is_active ?? true" /></div>
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$supplier->notes" rows="3" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Pemasok' }}</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
