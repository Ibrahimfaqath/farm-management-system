@extends('layouts.app', ['title' => 'Laporan Pakan'])

@section('content')
    <x-page-header title="Laporan Pakan" subtitle="Stok, pembelian, pemakaian, dan laju konsumsi" />

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
        <select name="farm_id" class="select select-sm select bg-base-100">
            <option value="">Semua peternakan</option>
            @foreach ($farms as $id => $name)
                <option value="{{ $id }}" @selected((int) ($farmId ?? 0) === $id)>{{ $name }}</option>
            @endforeach
        </select>
        <input name="date_from" type="date" value="{{ $window['from']->toDateString() }}" class="input input-sm input bg-base-100">
        <input name="date_to" type="date" value="{{ $window['to']->toDateString() }}" class="input input-sm input bg-base-100">
        <div class="flex gap-2">
            <button class="btn btn-neutral btn-sm">Terapkan</button>
            <a href="{{ route('reports.feed.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Nilai Stok" :value="'Rp'.number_format($totalStockValue, 0, ',', '.')" tone="primary" />
        <x-stat label="Nilai Pembelian" :value="'Rp'.number_format($totalPurchaseAmount, 0, ',', '.')" tone="info" />
        <x-stat label="Nilai Pemakaian" :value="'Rp'.number_format($totalConsumptionAmount, 0, ',', '.')" tone="warning" />
        <x-stat label="Item Menipis" :value="$lowStockCount" :tone="$lowStockCount > 0 ? 'warning' : 'success'" />
    </div>

    <x-panel padding="p-0">
        <div class="overflow-x-auto">
            <table class="table table-sm table-zebra">
                <thead>
                    <tr>
                        <th>Item Pakan</th>
                        <th class="text-right">Stok</th>
                        <th class="text-right">Batas Ulang</th>
                        <th class="text-right">Dibeli</th>
                        <th class="text-right">Dipakai</th>
                        <th class="text-right">Rata-rata/Hari</th>
                        <th class="text-right">Estimasi Habis</th>
                        <th class="text-right">Nilai Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $row->name }}</p>
                                <p class="text-[11px] text-base-content/45">{{ $row->code }} · {{ $row->category->label() }}</p>
                            </td>
                            <td class="text-right tabular-nums font-medium {{ (float) $row->current_stock <= 0 ? 'text-error' : '' }}">
                                {{ number_format((float) $row->current_stock, 2) }} <span class="text-[11px] text-base-content/50">{{ $row->unit->shortLabel() }}</span>
                            </td>
                            <td class="text-right tabular-nums text-base-content/65">{{ number_format((float) $row->reorder_point, 2) }}</td>
                            <td class="text-right tabular-nums">
                                {{ number_format((float) $row->purchase_qty, 2) }}
                                <span class="block text-[11px] text-base-content/45">{{ number_format((float) $row->purchase_amount, 0) }}</span>
                            </td>
                            <td class="text-right tabular-nums">
                                {{ number_format((float) $row->consumption_qty, 2) }}
                                <span class="block text-[11px] text-base-content/45">{{ number_format((float) $row->consumption_amount, 0) }}</span>
                            </td>
                            <td class="text-right tabular-nums text-base-content/65">{{ number_format((float) $row->daily_consumption, 2) }}</td>
                            <td class="text-right tabular-nums">
                                @if ($row->days_remaining === null)
                                    <span class="text-base-content/45">—</span>
                                @elseif ($row->days_remaining <= 7)
                                    <span class="text-warning">{{ $row->days_remaining }} hr</span>
                                @else
                                    {{ $row->days_remaining }} hr
                                @endif
                            </td>
                            <td class="text-right tabular-nums"><x-money :amount="$row->stock_value" /></td>
                            <td>
                                @if ((float) $row->current_stock <= 0)
                                    <x-badge label="Habis" class="badge-soft badge-error" />
                                @elseif ($row->is_low)
                                    <x-badge label="Menipis" class="badge-soft badge-warning" />
                                @else
                                    <x-badge label="Aman" class="badge-soft badge-success" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-panel>
@endsection