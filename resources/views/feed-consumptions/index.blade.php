@extends('layouts.app', ['title' => 'Pemakaian Pakan'])

@section('content')
    <x-page-header title="Pemakaian Pakan" subtitle="Pemakaian mengurangi stok dan tercatat pada jejak pergerakan">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.consume'))
                <a href="{{ route('inventory.index') }}" class="btn btn-sm">Cek Stok</a>
                <a href="{{ route('feed-consumptions.create') }}" class="btn btn-primary btn-sm">+ Catat Pemakaian</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Jumlah Terpakai Bulan Ini" :value="number_format($monthQuantity, 2)" />
        <x-stat label="Nilai Bulan Ini" :value="'Rp'.number_format($monthTotal, 0, ',', '.')" tone="warning" />
        <x-stat label="Total Catatan" :value="$consumptions->total()" />
        <x-stat label="Rata-rata per Catatan" :value="$consumptions->count() > 0 ? 'Rp'.number_format($monthTotal / max(1, $consumptions->count()), 0, ',', '.') : '—'" />
    </div>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-5">
            <select name="farm_id" class="select select-sm select bg-base-100">
                <option value="">Semua peternakan</option>
                @foreach ($farms as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['farm_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="barn_id" class="select select-sm select bg-base-100">
                <option value="">Semua kandang</option>
                @foreach ($barns as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['barn_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('feed-consumptions.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($consumptions->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pemakaian pakan"
                              description="Catat pemakaian agar stok berkurang dan biaya pakan dapat dihitung."
                              action-label="Catat Pemakaian" action-url="{{ route('feed-consumptions.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Kandang</th>
                            <th class="text-right">Item</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Nilai</th>
                            <th>Oleh</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consumptions as $consumption)
                            <tr>
                                <td class="font-mono text-xs">
                                    <a href="{{ route('feed-consumptions.show', $consumption) }}" class="text-primary hover:underline">{{ $consumption->consumption_number }}</a>
                                </td>
                                <td class="whitespace-nowrap">{{ $consumption->consumption_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $consumption->barn?->name ?? 'Tanpa kandang' }}</td>
                                <td class="text-right tabular-nums">{{ $consumption->items_count }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $consumption->total_quantity, 2) }}</td>
                                <td class="text-right"><x-money :amount="$consumption->total_amount" /></td>
                                <td class="text-base-content/65">{{ $consumption->creator?->name ?? '—' }}</td>
                                <td class="text-right"><a href="{{ route('feed-consumptions.show', $consumption) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $consumptions->links() }}</div>
        @endif
    </x-panel>
@endsection
