@extends('layouts.app', ['title' => 'Pembayaran'])

@section('content')
    <x-page-header title="Pembayaran Pelanggan" subtitle="Setiap pembayaran tercatat pada piutang dan arus kas">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">+ Catat Pembayaran</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat label="Diterima Bulan Ini" :value="'Rp'.number_format($monthTotal, 0, ',', '.')" tone="success" />
        <x-stat label="Total Catatan" :value="$payments->total()" />
        <x-stat label="Rata-rata" :value="$payments->count() > 0 ? 'Rp'.number_format($monthTotal / $payments->count(), 0, ',', '.') : '—'" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nomor pembayaran…" class="input input-sm input bg-base-100">
            <select name="customer_id" class="select select-sm select bg-base-100">
                <option value="">Semua pelanggan</option>
                @foreach ($customers as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['customer_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('payments.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($payments->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pembayaran" description="Catat pembayaran pelanggan untuk mengurangi piutang dan menambah kas."
                              action-label="Catat Pembayaran" action-url="{{ route('payments.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Faktur</th>
                            <th>Metode</th>
                            <th>Referensi</th>
                            <th>Dicatat Oleh</th>
                            <th class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="font-mono text-xs">{{ $payment->payment_number }}</td>
                                <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $payment->customer->name }}</td>
                                <td class="text-[11px]">
                                    @if ($payment->invoice)
                                        <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-primary hover:underline">{{ $payment->invoice->invoice_number }}</a>
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td>{{ $payment->method->label() }}</td>
                                <td class="text-[11px] text-base-content/60">{{ $payment->reference_number ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $payment->recorder?->name ?? '—' }}</td>
                                <td class="text-right font-medium text-success"><x-money :amount="$payment->amount" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $payments->links() }}</div>
        @endif
    </x-panel>
@endsection
