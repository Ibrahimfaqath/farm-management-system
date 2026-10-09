<div class="space-y-4">
    <x-panel title="Rekam Kesehatan" padding="p-0">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('health-records.create', ['animal_id' => $animal->id]) }}" class="btn btn-primary btn-xs">+ Catat</a>
            @endif
        </x-slot:actions>
        @if ($animal->healthRecords->isEmpty())
            <p class="p-4 text-sm text-base-content/55">Belum ada rekam kesehatan untuk {{ $animal->animal_code }}.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Gejala</th><th>Diagnosis</th><th>Status</th><th class="text-right">Biaya</th></tr></thead>
                    <tbody>
                        @foreach ($animal->healthRecords as $record)
                            <tr>
                                <td class="whitespace-nowrap">{{ $record->record_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $record->record_type->label() }}</td>
                                <td class="max-w-xs truncate">{{ $record->symptoms ?? '—' }}</td>
                                <td class="max-w-xs truncate">{{ $record->diagnosis ?? '—' }}</td>
                                <td><x-badge :label="$record->status->label()" :class="$record->status->badgeClass()" /></td>
                                <td class="text-right"><x-money :amount="$record->cost ?? 0" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    <x-panel title="Vaksinasi Terakhir" padding="p-0">
        @if ($animal->vaccinations->isEmpty())
            <p class="p-4 text-sm text-base-content/55">Belum ada catatan vaksin.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Vaksin</th><th>Batch</th><th>Diberikan</th><th>Jatuh Tempo</th></tr></thead>
                    <tbody>
                        @foreach ($animal->vaccinations as $vax)
                            <tr>
                                <td>{{ $vax->vaccine_name }}</td>
                                <td class="text-base-content/65">{{ $vax->batch_number ?? '—' }}</td>
                                <td>{{ $vax->administered_date->translatedFormat('d M Y') }}</td>
                                <td>
                                    {{ $vax->next_due_date?->translatedFormat('d M Y') ?? '—' }}
                                    @if ($vax->next_due_date && $vax->next_due_date->isPast())
                                        <x-badge label="Jatuh tempo" class="badge-soft badge-warning" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    <x-panel title="Pengobatan Terakhir" padding="p-0">
        @if ($animal->treatments->isEmpty())
            <p class="p-4 text-sm text-base-content/55">Belum ada pengobatan.</p>
        @else
            <ul class="divide-y divide-base-300">
                @foreach ($animal->treatments as $treatment)
                    <li class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">{{ $treatment->medication_name }}</p>
                            <p class="text-xs text-base-content/55">
                                {{ $treatment->treatment_date->translatedFormat('d M Y') }}
                                · {{ $treatment->dosage ?? ' dosis tidak dicatat' }}
                                · {{ $treatment->veterinarian?->name ?? 'Tanpa dokter' }}
                            </p>
                        </div>
                        <x-money :amount="$treatment->cost ?? 0" class="shrink-0 text-sm" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-panel>
</div>
