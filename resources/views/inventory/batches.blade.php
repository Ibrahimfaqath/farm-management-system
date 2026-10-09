@extends('layouts.app', ['title' => 'Batch Pakan'])

@section('content')
    <x-page-header title="Batch Pakan" subtitle="Telusur asal-usul stok per batch pembayaran" :back="route('inventory.index')" />

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-3">
            <select name="feed_item_id" class="select select-sm select bg-base-100">
                <option value="">Semua item pakan</option>
                @foreach ($feedItems as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['feed_item_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="status" class="select select-sm select bg-base-100">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('inventory.batches') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($batches->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada batch" description="Batch terbentuk otomatis ketika penerimaan barang dikonfirmasi." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Kode Batch</th>
                            <th>Item Pakan</th>
                            <th>Diterima</th>
                            <th class="text-right">Awal</th>
                            <th class="text-right">Sisa</th>
                            <th class="text-right">Terpakai</th>
                            <th>Kedaluwarsa</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            @php
                                $used = (float) $batch->original_quantity - (float) $batch->quantity_remaining;
                            @endphp
                            <tr>
                                <td class="font-mono text-xs">{{ $batch->batch_code }}</td>
                                <td class="font-medium">{{ $batch->feedItem?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $batch->received_date->translatedFormat('d M Y') }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $batch->original_quantity, 2) }}</td>
                                <td class="text-right tabular-nums font-medium">{{ number_format((float) $batch->quantity_remaining, 2) }}</td>
                                <td class="text-right tabular-nums text-base-content/65">{{ number_format($used, 2) }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($batch->expiry_date)
                                        {{ $batch->expiry_date->translatedFormat('d M Y') }}
                                        @if ($batch->isExpired())
                                            <x-badge label="Kedaluwarsa" class="badge-soft badge-error" />
                                        @endif
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="text-base-content/65">{{ $batch->storage_location ?? '—' }}</td>
                                <td>
                                    <x-badge :label="$batch->status->label()"
                                             :class="match ($batch->status->value) {
                                                 'active' => 'badge-soft badge-success',
                                                 'depleted' => 'badge-ghost',
                                                 'expired' => 'badge-soft badge-error',
                                                 default => 'badge-soft badge-warning',
                                             }" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $batches->links() }}</div>
        @endif
    </x-panel>
@endsection
