@extends('layouts.app', ['title' => $purchaseOrder->po_number])

@section('content')
    <x-page-header :title="$purchaseOrder->po_number"
                   :subtitle="$purchaseOrder->supplier->name.' · '.($purchaseOrder->order_date?->translatedFormat('d M Y') ?? '—')"
                   :back="route('purchase-orders.index')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.receive') && in_array($purchaseOrder->status->value, ['approved', 'partially_received'], true))
                <a href="{{ route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}" class="btn btn-primary btn-sm">
                    Catat Penerimaan
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($purchaseOrder->status->value === 'partially_received')
        <div class="mb-4">
            <x-alert-row tone="warning"
                         title="Penerimaan sebagian: {{ number_format($totalReceived, 2) }} dari {{ number_format($totalOrdered, 2) }} unit telah diterima"
                         :description="'Sisa yang belum diterima: '.number_format($totalOutstanding, 2).' unit. Pesanan tetap terbuka sampai diterima penuh.'" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Status" :value="$purchaseOrder->status->label()" :tone="$purchaseOrder->status->value === 'received' ? 'success' : 'warning'" />
        <x-stat label="Total Dipesan" :value="number_format($totalOrdered, 2)" />
        <x-stat label="Total Diterima" :value="number_format($totalReceived, 2)" tone="success" />
        <x-stat label="Belum Diterima" :value="number_format($totalOutstanding, 2)" :tone="$totalOutstanding > 0 ? 'error' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Rincian Pesanan" padding="p-0">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item Pakan</th>
                                <th class="text-right">Dipesan</th>
                                <th class="text-right">Diterima</th>
                                <th class="text-right">Sisa</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Nilai</th>
                                <th>Progres</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $line)
                                <tr>
                                    <td>
                                        <p class="font-medium">{{ $line->item->feedItem->name }}</p>
                                        <p class="text-[11px] text-base-content/45">{{ $line->item->feedItem->code }}</p>
                                    </td>
                                    <td class="text-right tabular-nums">{{ number_format($line->ordered, 2) }} <span class="text-[11px] text-base-content/50">{{ $line->item->unit->shortLabel() }}</span></td>
                                    <td class="text-right tabular-nums text-success">{{ number_format($line->received, 2) }}</td>
                                    <td class="text-right tabular-nums {{ $line->outstanding > 0 ? 'text-error' : 'text-base-content/45' }}">
                                        {{ number_format($line->outstanding, 2) }}
                                    </td>
                                    <td class="text-right tabular-nums"><x-money :amount="$line->item->unit_price" /></td>
                                    <td class="text-right tabular-nums"><x-money :amount="$line->item->line_total" /></td>
                                    <td class="w-32">
                                        <progress class="progress {{ $line->received_pct >= 100 ? 'progress-success' : 'progress-warning' }} h-1.5 w-full"
                                                max="100" value="{{ min(100, $line->received_pct) }}"></progress>
                                        <span class="text-[11px] text-base-content/50">{{ $line->received_pct }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-base-300 p-3">
                    <dl class="ml-auto w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-base-content/55">Subtotal</dt><dd><x-money :amount="$purchaseOrder->subtotal" /></dd></div>
                        @if ((float) $purchaseOrder->discount_amount > 0)
                            <div class="flex justify-between"><dt class="text-base-content/55">Diskon</dt><dd>−<x-money :amount="$purchaseOrder->discount_amount" /></dd></div>
                        @endif
                        @if ((float) $purchaseOrder->tax_amount > 0)
                            <div class="flex justify-between"><dt class="text-base-content/55">Pajak</dt><dd><x-money :amount="$purchaseOrder->tax_amount" /></dd></div>
                        @endif
                        <div class="flex justify-between border-t border-base-300 pt-1 font-medium">
                            <dt>Total</dt><dd><x-money :amount="$purchaseOrder->total" /></dd>
                        </div>
                    </dl>
                </div>
            </x-panel>

            <x-panel title="Riwayat Penerimaan" padding="p-0">
                @if ($purchaseOrder->goodsReceipts->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada penerimaan barang untuk pesanan ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Nomor</th><th>Tanggal</th><th>Status</th><th class="text-right">Nilai</th><th class="text-right"></th></tr></thead>
                            <tbody>
                                @foreach ($purchaseOrder->goodsReceipts as $receipt)
                                    <tr>
                                        <td class="font-mono text-xs">{{ $receipt->receipt_number }}</td>
                                        <td class="whitespace-nowrap">{{ $receipt->received_date->translatedFormat('d M Y') }}</td>
                                        <td><x-badge :label="$receipt->status->label()" :class="$receipt->status->badgeClass()" /></td>
                                        <td class="text-right"><x-money :amount="$receipt->total_amount" /></td>
                                        <td class="text-right"><a href="{{ route('goods-receipts.show', $receipt) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel title="Informasi Pesanan">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Pemasok</dt><dd class="text-right">{{ $purchaseOrder->supplier->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Termin</dt><dd>{{ $purchaseOrder->supplier->payment_terms_days }} hari</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Tanggal Pesan</dt><dd>{{ $purchaseOrder->order_date?->translatedFormat('d M Y') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Perkiraan Tiba</dt><dd>{{ $purchaseOrder->expected_date?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Dibuat Oleh</dt><dd>{{ $purchaseOrder->creator?->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-base-content/55">Disetujui Oleh</dt><dd>{{ $purchaseOrder->approver?->name ?? '—' }}</dd></div>
            </dl>
            @if ($purchaseOrder->notes)
                <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $purchaseOrder->notes }}</p>
            @endif
        </x-panel>
    </div>
@endsection
