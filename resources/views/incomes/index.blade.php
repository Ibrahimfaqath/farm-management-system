@extends('layouts.app', ['title' => 'Pendapatan'])

@section('content')
    <x-page-header title="Pendapatan Lain" subtitle="Pendapatan di luar penjualan susu yang dicatat lewat faktur">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <a href="{{ route('incomes.create') }}" class="btn btn-primary btn-sm">+ Catat Pendapatan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <x-alert-row tone="info" title="Penjualan susu dicatat melalui faktur"
                     description="Pendapatan dari penjualan susu dan produk lain tercatat pada modul Penjualan agar piutang dan kas tetap terhubung."
                     :url="route('sales-orders.index')" url-label="Buka Penjualan" />
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat label="Bulan Ini" :value="'Rp'.number_format($monthTotal, 0, ',', '.')" tone="success" />
        <x-stat label="Total Catatan" :value="$incomes->total()" />
        <x-stat label="Rata-rata" :value="$incomes->count() > 0 ? 'Rp'.number_format($monthTotal / $incomes->count(), 0, ',', '.') : '—'" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari deskripsi…" class="input input-sm input bg-base-100">
            <select name="category" class="select select-sm select bg-base-100">
                <option value="">Semua kategori</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('incomes.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($incomes->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pendapatan lain"
                              description="Contoh pendapatan lain: penjualan manure, penjualan anak, atau bantuan pemerintah."
                              action-label="Catat Pendapatan" action-url="{{ route('incomes.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th>Sumber</th>
                            <th>Metode</th>
                            <th class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incomes as $income)
                            <tr>
                                <td class="font-mono text-[11px]">{{ $income->income_number }}</td>
                                <td class="whitespace-nowrap">{{ $income->income_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $income->category->label() }}</td>
                                <td class="max-w-xs truncate">{{ $income->description }}</td>
                                <td class="text-base-content/65">{{ $income->source_name ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $income->payment_method->label() }}</td>
                                <td class="text-right font-medium text-success"><x-money :amount="$income->amount" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $incomes->links() }}</div>
        @endif
    </x-panel>
@endsection
