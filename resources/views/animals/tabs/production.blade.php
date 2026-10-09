@php
    $period = request()->query('days', 30);
    $from = now()->subDays((int) $period);
    $rows = $animal->milkProductions()
        ->where('production_date', '>=', $from)
        ->orderBy('production_date')
        ->get();
    $total = (float) $rows->sum('liters');
    $peak = $rows->max('liters') ?? 0;
@endphp

<div class="space-y-4">
    <x-panel title="Produksi Susu" padding="p-0">
        <x-slot:actions>
            <div class="flex items-center gap-1">
                @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $days => $label)
                    <a href="?tab=produksi&days={{ $days }}"
                       class="btn btn-xs {{ (int) $period === $days ? 'btn-neutral' : 'btn-ghost' }}">{{ $label }}</a>
                @endforeach
            </div>
            @if (auth()->user()->hasAbility('production.record'))
                <a href="{{ route('milk-productions.create', ['animal_id' => $animal->id]) }}" class="btn btn-primary btn-xs">+ Catat</a>
            @endif
        </x-slot:actions>

        @if ($rows->isEmpty())
            <p class="p-4 text-sm text-base-content/55">Belum ada produksi susu tercatat dalam {{ $period }} hari terakhir.</p>
        @else
            <div class="grid grid-cols-3 gap-3 border-b border-base-300 p-4">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-base-content/50">Total</p>
                    <p class="text-lg font-semibold tabular-nums">{{ number_format($total, 1) }} L</p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-base-content/50">Rata-rata</p>
                    <p class="text-lg font-semibold tabular-nums">{{ number_format($rows->avg('liters'), 1) }} L</p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-base-content/50">Tertinggi</p>
                    <p class="text-lg font-semibold tabular-nums">{{ number_format($peak, 1) }} L</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Tanggal</th><th>Sesi</th><th class="text-right">Liter</th><th class="text-right">Lemak %</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @foreach ($rows->reverse() as $row)
                            <tr>
                                <td class="whitespace-nowrap">{{ $row->production_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $row->session->label() }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $row->liters, 1) }}</td>
                                <td class="text-right tabular-nums">{{ $row->fat_percent ?? '—' }}</td>
                                <td class="max-w-xs truncate text-base-content/60">{{ $row->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
</div>
