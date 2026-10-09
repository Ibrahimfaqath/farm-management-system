@extends('layouts.app', ['title' => 'Catat Pendapatan'])

@section('content')
    <x-page-header title="Catat Pendapatan"
                   subtitle="Untuk pendapatan penjualan susu, gunakan faktur dan pembayaran pada modul Penjualan."
                   :back="route('incomes.index')" />

    <form method="POST" action="{{ route('incomes.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Data Pendapatan">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="income_date" label="Tanggal" type="date"
                              :value="$income->income_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.select name="category" label="Kategori" :options="$categories" :selected="$income->category?->value" required />
                <x-form.money name="amount" label="Jumlah" :value="0" required />
                <x-form.select name="payment_method" label="Metode" :options="$methods" :selected="$income->payment_method?->value" required />
                <x-form.input name="description" label="Deskripsi" :value="$income->description" required class="sm:col-span-2"
                              placeholder="Contoh: penjualan 2 ton manure" />
                <x-form.input name="source_name" label="Sumber / Penerima" :value="$income->source_name" />
                <x-form.input name="reference" label="Nomor Referensi" :value="$income->reference" />
            </div>
            <div class="mt-3">
                <x-form.textarea name="notes" label="Catatan" :value="$income->notes" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Pendapatan</button>
                <a href="{{ route('incomes.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
