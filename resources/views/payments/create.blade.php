@extends('layouts.app', ['title' => 'Catat Pembayaran'])

@section('content')
    <x-page-header title="Catat Pembayaran"
                   subtitle="Pembayaran mengurangi piutang dan menambah kas pada saat yang sama."
                   :back="route('payments.index')" />

    <form method="POST" action="{{ route('payments.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Faktur dan Pelanggan">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="invoice_id" label="Faktur" :options="$openInvoices"
                               :selected="old('invoice_id', $invoice?->id)" placeholder="— Pembayaran tanpa faktur —" />
                <x-form.select name="customer_id" label="Pelanggan" :options="$customers"
                               :selected="old('customer_id', $invoice?->customer_id)" required />
            </div>
            @error('invoice_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('customer_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Detail Pembayaran">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="payment_date" label="Tanggal" type="date" :value="$payment->payment_date?->toDateString() ?? now()->toDateString()" required />
                <x-form.money name="amount" label="Jumlah" :value="$invoice ? $invoice->balance() : 0" required />
                <x-form.select name="method" label="Metode" :options="$methods" :selected="$payment->method?->value ?? 'transfer'" required />
                <x-form.input name="reference_number" label="Nomor Referensi" :value="$payment->reference_number"
                              placeholder="Nomor transaksi bank / nota" />
            </div>
            @error('amount')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('method')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            <div class="mt-3">
                <x-form.textarea name="notes" label="Catatan" :value="$payment->notes" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Pembayaran</button>
                <a href="{{ route('payments.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
