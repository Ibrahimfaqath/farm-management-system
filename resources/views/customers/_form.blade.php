@php $editing = $customer->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Pelanggan' : 'Tambah Pelanggan'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$customer->name : 'Tambah Pelanggan'" :back="route('customers.index')" />

    <form method="POST" action="{{ $editing ? route('customers.update', $customer) : route('customers.store') }}"
          class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Identitas">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" label="Kode Pelanggan" :value="$customer->code" required maxlength="30" placeholder="CUS-001" />
                <x-form.input name="name" label="Nama Pelanggan" :value="$customer->name" required maxlength="150" />
                <x-form.select name="type" label="Jenis Pelanggan" :options="$types" :selected="$customer->type" required />
                <x-form.select name="payment_terms_days" label="Termin Pembayaran" :options="$termOptions"
                               :selected="$customer->payment_terms_days ?? 30" required />
                <x-form.money name="credit_limit" label="Limit Kredit" :value="$customer->credit_limit ?? 0" />
                <x-form.input name="contact_person" label="Nama Kontak" :value="$customer->contact_person" />
                <x-form.input name="phone" label="Telepon" :value="$customer->phone" />
                <x-form.input name="email" label="Email" type="email" :value="$customer->email" />
                <x-form.input name="city" label="Kota" :value="$customer->city" />
                <x-form.input name="address" label="Alamat" :value="$customer->address" />
            </div>
            <div class="mt-4"><x-form.checkbox name="is_active" label="Pelanggan aktif" :checked="$customer->is_active ?? true" /></div>
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$customer->notes" rows="3" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Pelanggan' }}</button>
                <a href="{{ route('customers.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
