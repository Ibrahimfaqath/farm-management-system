@extends('layouts.app', ['title' => 'Pesanan '.$salesOrder->so_number])

@section('content')
    <x-page-header :title="$salesOrder->so_number"
                   :subtitle="$salesOrder->customer->name.' · '.$salesOrder->order_date->translatedFormat('d M Y')"
                   :back="route('sales-orders.index')">
        <x-slot:actions>
            @if (! $salesOrder->invoice && $salesOrder->status->value !== 'cancelled' && auth()->user()->hasAbility('invoice.manage'))
                <form method="POST" action="{{ route('sales-orders.invoice', $salesOrder) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm">Buat Faktur</button>
                </form>
            @endif
            @if ($salesOrder->invoice && $salesOrder->invoice->balance() > 0 && auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create', ['invoice_id' => $salesOrder->invoice->id]) }}" class="btn btn-primary btn-sm">Catat Pembayaran</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($salesOrder->invoice)
        <div class="mb-4">
            <x-alert-row tone="info"
                         title="Faktur {{ $salesOrder->invoice->invoice_number }} terbit"
                         :description="'Pendapatan diakui Rp'.number_format((float) $salesOrder->invoice->total, 0).', piutang Rp'.number_format($salesOrder->invoice->balance(), 0).'. Kas berubah hanya setelah pembayaran.'" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Status" :value="$salesOrder->status->label()" />
        <x-stat label="Item" :value="$salesOrder->items->count()" />
        <x-stat label="Nilai Pesanan" :value="'Rp'.number_format((float) $salesOrder->total, 0, ',', '.')" />
        <x-stat label="Faktur" :value="$salesOrder->invoice?->invoice_number ?? 'Belum dibuat'" :tone="$salesOrder->invoice ? 'success' : 'warning'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Rincian Pesanan" padding="p-0">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Barang</th><th class="text-right">Jumlah</th><th class="text-right">Harga</th><th class="text-right">Nilai</th></tr></thead>
                        <tbody>
                            @foreach ($salesOrder->items as $item)
                                <tr>
                                    <td class="font-medium">{{ $item->description }}</td>
                                    <td class="text-right tabular-nums">{{ number_format((float) $item->quantity, 2) }} <span class="text-[11px] text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                    <td class="text-right tabular-nums"><x-money :amount="$item->unit_price" /></td>
                                    <td class="text-right tabular-nums"><x-money :amount="$item->line_total" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-base-300 p-3">
                    <dl class="ml-auto w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-base-content/55">Subtotal</dt><dd><x-money :amount="$salesOrder->subtotal" /></dd></div>
                        @if ((float) $salesOrder->discount_amount > 0)
                            <div class="flex justify-between"><dt class="text-base-content/55">Diskon</dt><dd>−<x-money :amount="$salesOrder->discount_amount" /></dd></div>
                        @endif
                        @if ((float) $salesOrder->tax_amount > 0)
                            <div class="flex justify-between"><dt class="text-base-content/55">Pajak</dt><dd><x-money :amount="$salesOrder->tax_amount" /></dd></div>
                        @endif
                        <div class="flex justify-between border-t border-base-300 pt-1 font-medium"><dt>Total</dt><dd><x-money :amount="$salesOrder->total" /></dd></div>
                    </dl>
                </div>
            </x-panel>
        </div>

        <x-panel title="Informasi Pesanan">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Pelanggan</dt>
                    <dd><a href="{{ route('customers.show', $salesOrder->customer) }}" class="text-primary hover:underline">{{ $salesOrder->customer->name }}</a></dd>
                </div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Termin</dt><dd>{{ $salesOrder->customer->payment_terms_days }} hari</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Dibuat Oleh</dt><dd>{{ $salesOrder->creator?->name ?? '—' }}</dd></div>
            </dl>
            @if ($salesOrder->notes)
                <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed">{{ $salesOrder->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection
