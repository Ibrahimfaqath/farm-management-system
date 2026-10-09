@extends('layouts.app', ['title' => 'Peminahan '.$record->animal->animal_code])

@section('content')
    <x-page-header :title="'Peminahan · '.$record->animal->animal_code"
                   :subtitle="$record->record_type->label().' · '.$record->breeding_date->translatedFormat('d M Y')"
                   :back="route('breeding-records.index')">
        <x-slot:actions>
            @if ($record->pregnancyChecks->isEmpty() && auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('pregnancy-checks.create', ['breeding_record_id' => $record->id]) }}" class="btn btn-primary btn-sm">
                    Catat Pemeriksaan
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-panel title="Detail Peminahan">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-base-content/55">Ternak Betina</dt>
                        <dd><a href="{{ route('animals.show', $record->animal) }}" class="text-primary hover:underline">{{ $record->animal->animal_code }}</a></dd>
                    </div>
                    <div><dt class="text-base-content/55">Pejantan</dt><dd>{{ $record->sireLabel() }}</dd></div>
                    <div><dt class="text-base-content/55">Tanggal</dt><dd>{{ $record->breeding_date->translatedFormat('d M Y') }}</dd></div>
                    <div><dt class="text-base-content/55">Inseminator</dt><dd>{{ $record->inseminator ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/55">Batch Semen</dt><dd>{{ $record->semen_batch ?? '—' }}</dd></div>
                    <div><dt class="text-base-content/55">Perkiraan Birahi Berikutnya</dt><dd>{{ $record->expected_next_heat_date?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                </dl>
                @if ($record->heat_signs)
                    <p class="mt-3 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed">{{ $record->heat_signs }}</p>
                @endif
                @if ($record->notes)
                    <p class="mt-2 rounded-md bg-base-200 p-2.5 text-xs leading-relaxed">{{ $record->notes }}</p>
                @endif
            </x-panel>

            <x-panel title="Riwayat Pemeriksaan Kehamilan" padding="p-0">
                @if ($record->pregnancyChecks->isEmpty())
                    <div class="p-4">
                        <x-empty-state title="Belum ada pemeriksaan kehamilan"
                                      description="Peminahan saja belum cukup untuk menyatakan hewan hamil. Catat hasil pemeriksaan."
                                      action-label="Catat Pemeriksaan" action-url="{{ route('pregnancy-checks.create', ['breeding_record_id' => $record->id]) }}" />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Tanggal</th><th>Metode</th><th>Hasil</th><th>Perkiraan Lahir</th><th>Dokter</th></tr></thead>
                            <tbody>
                                @foreach ($record->pregnancyChecks as $check)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ $check->check_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $check->method->label() }}</td>
                                        <td><x-badge :label="$check->result->label()" :class="$check->result->badgeClass()" /></td>
                                        <td class="whitespace-nowrap">{{ $check->expectedDueDate()?->translatedFormat('d M Y') ?? '—' }}</td>
                                        <td class="text-base-content/65">{{ $check->veterinarian?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel title="Status Ternak" description="Status breeding mengikuti hasil pemeriksaan, bukan asumsi peminahan.">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">Breeding</dt>
                    <dd><x-badge :label="$record->animal->breeding_status->label()" :class="$record->animal->breeding_status->badgeClass()" /></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">Produksi</dt>
                    <dd><x-badge :label="$record->animal->production_status->label()" :class="$record->animal->production_status->badgeClass()" /></dd>
                </div>
            </dl>
        </x-panel>
    </div>
@endsection
