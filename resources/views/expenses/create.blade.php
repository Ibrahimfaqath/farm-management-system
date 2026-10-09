@extends('layouts.app', ['title' => 'Catat Biaya'])

@section('content')
    <x-page-header title="Catat Biaya"
                   subtitle="Biaya dicatat beserta konsekuensi kas keluarnya agar angka tidak terpisah."
                   :back="route('expenses.index')" />

    <form method="POST" action="{{ route('expenses.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Data Biaya">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="expense_date" label="Tanggal" type="date"
                              :value="$expense->expense_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.select name="category" label="Kategori" :options="$categories" :selected="$expense->category?->value" required />
                <x-form.money name="amount" label="Jumlah" :value="0" required />
                <x-form.select name="payment_method" label="Metode Pembayaran" :options="$methods" :selected="$expense->payment_method?->value" required />
                <x-form.input name="description" label="Deskripsi" :value="$expense->description" required class="sm:col-span-2"
                              placeholder="Contoh: perbaikan atap gudang" />
                <x-form.select name="supplier_id" label="Pemasok terdaftar" :options="$suppliers" :selected="$expense->supplier_id"
                               placeholder="— Bukan pemasok terdaftar —" />
                <x-form.input name="vendor_name" label="Nama Vendor" :value="$expense->vendor_name" placeholder="Isi bila bukan pemasok terdaftar" />
                <x-form.input name="reference" label="Nomor Referensi / Nota" :value="$expense->reference" />
            </div>
            @error('vendor_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            <div class="mt-3">
                <x-form.textarea name="notes" label="Catatan" :value="$expense->notes" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Biaya</button>
                <a href="{{ route('expenses.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
