@extends('layouts.app', ['title' => 'Pemakaian '.$consumption->consumption_number])

@section('content')
    <x-page-header :title="$consumption->consumption_number"
                   :subtitle="($consumption->barn?->name ?? 'Tanpa kandang').' · '.$consumption->consumption_date->translatedFormat('d M Y')"
                   :back="route('feed-consumptions.index')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.consume'))
                <button class="btn btn-outline btn-sm btn-error" onclick="document.getElementById('reverse-modal').showModal()">Koreksi</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($consumption->allow_negative_stock)
        <div class="mb-4">
            <x-alert-row tone="error" title="Catatan ini memakai override stok negatif"
                         :description="'Alasan: '.($consumption->override_reason ?? 'tidak dicatat').($consumption->overrideBy ? ' · disetujui oleh '.$consumption->overrideBy->name : '')" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Total Kuantitas" :value="number_format((float) $consumption->total_quantity, 2)" />
        <x-stat label="Nilai Pemakaian" :value="'Rp'.number_format((float) $consumption->total_amount, 0, ',', '.')" tone="warning" />
        <x-stat label="Jumlah Hewan" :value="$consumption->animal_count" />
        <x-stat label="Rata-rata per Hewan" :value="number_format((float) $consumption->average_per_animal, 2)"
               :hint="$consumption->barn?->name" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Dampak terhadap Stok" padding="p-0"
                     description="Stok sebelum dan sesudah pemakaian ini, beserta batch yang terpakai.">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item Pakan</th>
                                <th class="text-right">Dipakai</th>
                                <th>Batch</th>
                                <th class="text-right">Stok Sebelum</th>
                                <th class="text-right">Stok Sesudah</th>
                                <th class="text-right">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($consumption->items as $item)
                                <tr>
                                    <td class="font-medium">{{ $item->feedItem?->name }}</td>
                                    <td class="text-right tabular-nums">{{ number_format((float) $item->quantity, 2) }} <span class="text-[11px] text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                    <td class="font-mono text-[11px]">{{ $item->batch?->batch_code ?? 'FIFO otomatis' }}</td>
                                    <td class="text-right tabular-nums text-base-content/65">{{ number_format((float) $item->stock_before, 2) }}</td>
                                    <td class="text-right tabular-nums font-medium {{ (float) $item->stock_after < 0 ? 'text-error' : 'text-success' }}">
                                        {{ number_format((float) $item->stock_after, 2) }}
                                    </td>
                                    <td class="text-right tabular-nums"><x-money :amount="$item->line_total" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>

            <x-panel title="Atribusi ke Hewan" padding="p-0"
                     description="Detail operasional per hewan. Atribusi ini tidak mengurangi stok kedua kali.">
                @if ($groupedByAnimal->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">
                        Pemakaian ini dicatat pada level kandang. Atribusi per hewan bersifat opsional.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Hewan</th><th>Item</th><th class="text-right">Estimasi</th></tr></thead>
                            <tbody>
                                @foreach ($groupedByAnimal as $animalId => $allocations)
                                    @foreach ($allocations as $allocation)
                                        <tr>
                                            <td>
                                                @if ($allocation->animal)
                                                    <a href="{{ route('animals.show', $allocation->animal) }}" class="text-primary hover:underline">{{ $allocation->animal->animal_code }}</a>
                                                @else
                                                    <span class="text-base-content/45">Hewan #{{ $animalId }}</span>
                                                @endif
                                            </td>
                                            <td class="text-base-content/70">{{ $allocation->item?->feedItem?->name ?? '—' }}</td>
                                            <td class="text-right tabular-nums">{{ number_format((float) $allocation->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>

            <x-panel title="Pergerakan Stok" padding="p-0">
                @if ($movements->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">Belum ada pergerakan stok.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Item</th><th>Jenis</th><th class="text-right">Jumlah</th><th>Batch</th></tr></thead>
                            <tbody>
                                @foreach ($movements as $movement)
                                    <tr>
                                        <td>{{ $movement->feedItem?->name }}</td>
                                        <td><x-badge :label="$movement->type->label()" :class="$movement->type->badgeClass()" /></td>
                                        <td class="text-right tabular-nums {{ $movement->direction()->sign() > 0 ? 'text-success' : 'text-warning' }}">
                                            {{ $movement->direction()->sign() > 0 ? '+' : '−' }}{{ number_format((float) $movement->quantity, 2) }}
                                        </td>
                                        <td class="font-mono text-[11px]">{{ $movement->batch?->batch_code ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel title="Informasi">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Peternakan</dt><dd class="text-right">{{ $consumption->farm?->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Kandang</dt><dd class="text-right">{{ $consumption->barn?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Dicatat Oleh</dt><dd class="text-right">{{ $consumption->creator?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-base-content/55">Tanggal</dt><dd>{{ $consumption->consumption_date->translatedFormat('d M Y') }}</dd></div>
                </dl>
                @if ($consumption->notes)
                    <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $consumption->notes }}</p>
                @endif
            </x-panel>
        </div>
    </div>

    <dialog id="reverse-modal" class="modal">
        <div class="modal-box max-w-lg">
            <h3 class="text-base font-semibold">Koreksi Pemakaian</h3>
            <p class="mt-1 text-xs text-base-content/60">
                Catatan asli tetap tersimpan. Koreksi membuat pergerakan stok masuk baru dengan alasan yang dicatat.
            </p>
            <form method="POST" action="{{ route('feed-consumptions.reverse', $consumption) }}" class="mt-4 space-y-3">
                @csrf
                <x-form.input name="reason" label="Alasan Koreksi" required minlength="10"
                              placeholder="Contoh: input ganda oleh petugas"
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('reverse-modal').close()">Batal</button>
                    <button class="btn btn-error btn-sm">Koreksi</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
    </dialog>
@endsection
