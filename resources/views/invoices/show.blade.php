@extends('layouts.app', ['title' => 'Faktur '.$invoice->invoice_number])

@section('content')
    <x-page-header :title="$invoice->invoice_number"
                   :subtitle="$invoice->customer->name.' · '.$invoice->invoice_date->translatedFormat('d M Y')"
                   :back="route('invoices.index')">
        <x-slot:actions>
            @if ($balance > 0 && auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary btn-sm">Catat Pembayaran</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($invoice->isOverdue())
        <div class="mb-4">
            <x-alert-row tone="error" title="Faktur terlambat {{ $invoice->daysOverdue() }} hari"
                         :description="'Sisa tagihan Rp'.number_format($balance, 0).'. Jatuh tempo '.$invoice->due_date->translatedFormat('d M Y').'.'" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Total Faktur" :value="'Rp'.number_format((float) $invoice->total, 0, ',', '.')" />
        <x-stat label="Sudah Dibayar" :value="'Rp'.number_format($paidAmount, 0, ',', '.')" tone="info" />
        <x-stat label="Sisa Piutang" :value="'Rp'.number_format($balance, 0, ',', '.')" :tone="$balance > 0 ? 'warning' : 'success'" />
        <x-stat label="Status" :value="$invoice->status->label()" :tone="$balance > 0 ? 'warning' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Rincian Faktur" padding="p-0">
                @if ($invoice->salesOrder)
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Barang</th><th class="text-right">Jumlah</th><th class="text-right">Harga</th><th class="text-right">Nilai</th></tr></thead>
                            <tbody>
                                @foreach ($invoice->salesOrder->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-right tabular-nums">{{ number_format((float) $item->quantity, 2) }} <span class="text-[11px] text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                        <td class="text-right tabular-nums"><x-money :amount="$item->unit_price" /></td>
                                        <td class="text-right tabular-nums"><x-money :amount="$item->line_total" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="p-4 text-sm text-base-content/55">Faktur ini dibuat langsung tanpa pesanan penjualan.</p>
                @endif

                <div class="border-t border-base-300 p-3">
                    <dl class="ml-auto w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-base-content/55">Subtotal</dt><dd><x-money :amount="$invoice->subtotal" /></dd></div>
                        @if ((float) $invoice->discount_amount > 0)
                            <div class="flex justify-between"><dt class="text-base-content/55">Diskon</dt><dd>−<x-money :amount="$invoice->discount_amount" /></dd></div>
                        @endif
                        @if ((float) $invoice->tax_amount > 0)
                            <div class="flex justify-between"><dt class="text-base-content/55">Pajak</dt><dd><x-money :amount="$invoice->tax_amount" /></dd></div>
                        @endif
                        <div class="flex justify-between border-t border-base-300 pt-1 font-medium"><dt>Total</dt><dd><x-money :amount="$invoice->total" /></dd></div>
                    </dl>
                </div>
            </x-panel>

            <x-panel title="Riwayat Pembayaran" padding="p-0"
                     description="Setiap pembayaran juga mencatat transaksi kas masuk.">
                @if ($invoice->payments->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada pembayaran untuk faktur ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Metode</th><th>Referensi</th><th>Dicatat Oleh</th><th class="text-right">Jumlah</th></tr></thead>
                            <tbody>
                                @foreach ($invoice->payments as $payment)
                                    <tr>
                                        <td class="font-mono text-[11px]">{{ $payment->payment_number }}</td>
                                        <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $payment->method->label() }}</td>
                                        <td class="text-[11px] text-base-content/60">{{ $payment->reference_number ?? '—' }}</td>
                                        <td class="text-base-content/65">{{ $payment->recorder?->name ?? '—' }}</td>
                                        <td class="text-right"><x-money :amount="$payment->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel title="Informasi Faktur">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Pelanggan</dt>
                        <dd><a href="{{ route('customers.show', $invoice->customer) }}" class="text-primary hover:underline">{{ $invoice->customer->name }}</a></dd>
                    </div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Tanggal</dt><dd>{{ $invoice->invoice_date->translatedFormat('d M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Termin</dt><dd>{{ $invoice->payment_terms_days }} hari</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Jatuh Tempo</dt><dd>{{ $invoice->due_date->translatedFormat('d M Y') }}</dd></div>
                    @if ($invoice->salesOrder)
                        <div class="flex justify-between gap-3"><dt class="text-base-content/55">Pesanan</dt>
                            <dd><a href="{{ route('sales-orders.show', $invoice->salesOrder) }}" class="text-primary hover:underline">{{ $invoice->salesOrder->so_number }}</a></dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Dibuat Oleh</dt><dd>{{ $invoice->creator?->name ?? '—' }}</dd></div>
                </dl>
                @if ($invoice->notes)
                    <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed">{{ $invoice->notes }}</p>
                @endif
            </x-panel>
        </div>
    </div>
@endsection
