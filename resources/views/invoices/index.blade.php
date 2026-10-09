@extends('layouts.app', ['title' => 'Faktur'])

@section('content')
    <x-page-header title="Faktur" subtitle="Piutang bertambah saat faktur terbit; kas bertambah saat pembayaran diterima">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">+ Catat Pembayaran</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Total Piutang" :value="'Rp'.number_format($receivable, 0, ',', '.')" tone="warning" />
        <x-stat label="Piutang Terlambat" :value="'Rp'.number_format($overdueAmount, 0, ',', '.')" :tone="$overdueAmount > 0 ? 'error' : 'success'" />
        <x-stat label="Faktur Terlambat" :value="$overdueCount" :tone="$overdueCount > 0 ? 'error' : 'success'" />
        <x-stat label="Total Faktur" :value="$invoices->total()" />
    </div>

    @if ($overdueCount > 0)
        <div class="mb-4">
            <x-alert-row tone="error" title="{{ $overdueCount }} faktur lewat jatuh tempo"
                         :description="'Total piutang terlambat Rp'.number_format($overdueAmount, 0).'. Faktor dianggap terlambat bila tanggal jatuh tempo sudah lewat dan masih ada sisa tagihan.'"
                         :url="route('invoices.index', ['overdue' => 1])" url-label="Tampilkan" />
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-4">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nomor faktur…" class="input input-sm input bg-base-100">
            <select name="status" class="select select-sm select bg-base-100">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="customer_id" class="select select-sm select bg-base-100">
                <option value="">Semua pelanggan</option>
                @foreach ($customers as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['customer_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] ?? false) class="checkbox checkbox-sm checkbox-primary">
                    Terlambat
                </label>
                <button class="btn btn-neutral btn-sm">Cari</button>
            </div>
        </form>

        @if ($invoices->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada faktur" description="Faktur dibuat dari pesanan penjualan. Faktur naikkan pendapatan dan piutang tanpa mengubah kas."
                              action-label="Buat Pesanan Penjualan" action-url="{{ route('sales-orders.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Dibayar</th>
                            <th class="text-right">Sisa</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="font-mono text-xs">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-primary hover:underline">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td>{{ $invoice->customer->name }}</td>
                                <td class="whitespace-nowrap">{{ $invoice->invoice_date->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap">
                                    {{ $invoice->due_date->translatedFormat('d M Y') }}
                                    @if ($invoice->isOverdue())
                                        <x-badge label="{{ $invoice->daysOverdue() }} hari" class="badge-soft badge-error" />
                                    @endif
                                </td>
                                <td><x-badge :label="$invoice->status->label()" :class="$invoice->status->badgeClass()" /></td>
                                <td class="text-right"><x-money :amount="$invoice->total" /></td>
                                <td class="text-right text-base-content/65"><x-money :amount="$invoice->paid_amount" /></td>
                                <td class="text-right font-medium {{ $invoice->balance() > 0 ? 'text-warning' : 'text-success' }}">
                                    <x-money :amount="$invoice->balance()" />
                                </td>
                                <td class="text-right">
                                    @if ($invoice->balance() > 0 && auth()->user()->hasAbility('payment.record'))
                                        <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-ghost btn-xs">Bayar</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $invoices->links() }}</div>
        @endif
    </x-panel>
@endsection
