@extends('layouts.app', ['title' => 'Pergerakan Stok'])

@section('content')
    <x-page-header title="Pergerakan Stok" subtitle="Jejak audit perubahan stok" :back="route('inventory.index')" />

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="feed_item_id" class="select select-sm select bg-base-100">
                <option value="">Semua item</option>
                @foreach ($feedItems as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['feed_item_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="type" class="select select-sm select bg-base-100">
                <option value="">Semua jenis</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('inventory.movements') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($movements->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada pergerakan stok" description="Pergerakan muncul otomatis saat penerimaan barang, pemakaian, atau penyesuaian dicatat." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Item</th>
                            <th>Jenis</th>
                            <th class="text-right">Jumlah</th>
                            <th>Batch</th>
                            <th>Referensi</th>
                            <th>Alasan</th>
                            <th>Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr>
                                <td class="whitespace-nowrap">{{ $movement->movement_date->translatedFormat('d M Y') }}</td>
                                <td class="font-medium">{{ $movement->feedItem?->name ?? '—' }}</td>
                                <td><x-badge :label="$movement->type->label()" :class="$movement->type->badgeClass()" /></td>
                                <td class="text-right tabular-nums font-medium {{ $movement->direction()->sign() > 0 ? 'text-success' : 'text-warning' }}">
                                    {{ $movement->direction()->sign() > 0 ? '+' : '−' }}{{ number_format((float) $movement->quantity, 2) }}
                                    <span class="text-[11px] text-base-content/50">{{ $movement->unit->shortLabel() }}</span>
                                </td>
                                <td class="font-mono text-[11px]">{{ $movement->batch?->batch_code ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $movement->reference ?? '—' }}</td>
                                <td class="max-w-xs truncate text-base-content/65">{{ $movement->reason ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $movement->creator?->name ?? 'Sistem' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $movements->links() }}</div>
        @endif
    </x-panel>
@endsection
