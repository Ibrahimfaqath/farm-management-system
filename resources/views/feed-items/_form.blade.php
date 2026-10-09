@php $editing = $feedItem->exists; @endphp
@extends('layouts.app', ['title' => $editing ? 'Ubah Item Pakan' : 'Tambah Item Pakan'])

@section('content')
    <x-page-header :title="$editing ? 'Ubah '.$feedItem->name : 'Tambah Item Pakan'"
                   subtitle="Harga satuan dipakai untuk menghitung nilai pemakaian dan penyesuaian stok."
                   :back="route('feed-items.index')" />

    <form method="POST" action="{{ $editing ? route('feed-items.update', $feedItem) : route('feed-items.store') }}"
          class="max-w-3xl space-y-4">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-panel title="Identitas">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="code" label="Kode Item" :value="$feedItem->code" required maxlength="30" placeholder="FD-001" />
                <x-form.input name="name" label="Nama Pakan" :value="$feedItem->name" required maxlength="150" />
                <x-form.select name="category" label="Kategori" :options="$categories" :selected="$feedItem->category?->value" required />
                <x-form.select name="unit" label="Satuan" :options="$units" :selected="$feedItem->unit?->value" required />
                <x-form.money name="price_per_unit" label="Harga Satuan" :value="$feedItem->price_per_unit ?? 0" required />
                <x-form.input name="storage_location" label="Lokasi Penyimpanan" :value="$feedItem->storage_location" maxlength="100" />
            </div>
        </x-panel>

        <x-panel title="Ambang Pemesanan" description="Nilai ini hanya memunculkan peringatan, tidak memesan otomatis.">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="reorder_point" label="Batas Stok Minimum" type="number" step="0.01" min="0"
                              :value="$feedItem->reorder_point ?? 0" hint="Peringatan muncul saat stok menyentuh nilai ini." />
                <x-form.input name="reorder_quantity" label="Saran Kuantitas Pesan" type="number" step="0.01" min="0"
                              :value="$feedItem->reorder_quantity ?? 0" />
                <x-form.input name="shelf_life_days" label="Masa Simpan (hari)" type="number" min="1"
                              :value="$feedItem->shelf_life_days" hint="Kosongkan jika tidak ada kedaluwarsa." />
            </div>
            <div class="mt-4"><x-form.checkbox name="is_active" label="Item aktif digunakan" :checked="$feedItem->is_active ?? true" /></div>
        </x-panel>

        <x-panel title="Catatan">
            <x-form.textarea name="notes" label="Catatan" :value="$feedItem->notes" rows="3" />
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">{{ $editing ? 'Simpan Perubahan' : 'Simpan Item Pakan' }}</button>
                <a href="{{ route('feed-items.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
