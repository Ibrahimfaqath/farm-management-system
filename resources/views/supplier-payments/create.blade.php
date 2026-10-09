@extends('layouts.app', ['title' => 'Bayar Pemasok'])

@section('content')
    <x-page-header title="Bayar Pemasok"
                   subtitle="Pembayaran mengurangi utang dan tercatat sebagai kas keluar."
                   :back="route('supplier-payments.index')" />

    <form method="POST" action="{{ route('supplier-payments.store') }}" class="max-w-3xl space-y-4">
        @csrf

        <x-panel title="Sumber Pembayaran">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="goods_receipt_id" label="Penerimaan Barang" :options="$openReceipts"
                               :selected="old('goods_receipt_id', $receipt?->id)"
                               placeholder="— Pembayaran umum pemasok —" />
                <x-form.select name="supplier_id" label="Pemasok" :options="$suppliers"
                               :selected="old('supplier_id', $receipt?->supplier_id)" required />
            </div>
            @error('goods_receipt_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            @error('supplier_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </x-panel>

        <x-panel title="Detail Pembayaran">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="payment_date" label="Tanggal" type="date" :value="now()->toDateString()" required />
                <x-form.money name="amount" label="Jumlah" :value="$receipt ? $receipt->outstandingAmount() : 0" required />
                <x-form.select name="method" label="Metode" :options="$methods" :selected="$payment->method?->value ?? 'transfer'" required />
                <x-form.input name="reference_number" label="Nomor Referensi" :value="$payment->reference_number" />
            </div>
            @error('amount')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            <div class="mt-3">
                <x-form.textarea name="notes" label="Catatan" :value="$payment->notes" rows="2" />
            </div>
        </x-panel>

        <div class="rounded-box border border-base-300 bg-base-100 p-4">
            <x-form.actions>
                <button class="btn btn-primary btn-sm">Simpan Pembayaran</button>
                <a href="{{ route('supplier-payments.index') }}" class="btn btn-ghost btn-sm">Batal</a>
            </x-form.actions>
        </div>
    </form>
@endsection
