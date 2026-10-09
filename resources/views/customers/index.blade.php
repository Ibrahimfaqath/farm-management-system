@extends('layouts.app', ['title' => 'Pelanggan'])

@section('content')
    <x-page-header title="Pelanggan" subtitle="Basis data pelanggan beserta piutang berjalan">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('sales.manage'))
                <a href="{{ route('sales-orders.create') }}" class="btn btn-sm">+ Pesanan Penjualan</a>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">+ Tambah Pelanggan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-3">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama atau kode…" class="input input-sm input bg-base-100">
            <select name="type" class="select select-sm select bg-base-100">
                <option value="">Semua jenis</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Cari</button>
                <a href="{{ route('customers.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($customers->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pelanggan" description="Tambahkan pelanggan sebelum membuat pesanan penjualan."
                              action-label="Tambah Pelanggan" action-url="{{ route('customers.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Kontak</th>
                            <th class="text-right">Termin</th>
                            <th class="text-right">Faktur</th>
                            <th class="text-right">Piutang</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td class="font-mono text-xs">{{ $customer->code }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}" class="font-medium text-primary hover:underline">{{ $customer->name }}</a>
                                </td>
                                <td class="text-base-content/70">{{ $types[$customer->type] ?? $customer->type }}</td>
                                <td class="text-base-content/65">{{ $customer->contact_person ?? '—' }}<br><span class="text-[11px]">{{ $customer->phone ?? '—' }}</span></td>
                                <td class="text-right tabular-nums">{{ $customer->payment_terms_days }} hr</td>
                                <td class="text-right tabular-nums">{{ $customer->invoices_count }}</td>
                                <td class="text-right tabular-nums {{ (float) $customer->balance > 0 ? 'font-medium text-warning' : 'text-base-content/45' }}">
                                    <x-money :amount="$customer->balance" />
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @if (auth()->user()->hasAbility('sales.manage'))
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-ghost btn-xs">Ubah</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $customers->links() }}</div>
        @endif
    </x-panel>
@endsection
