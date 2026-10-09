@extends('layouts.app', ['title' => 'Peminahan'])

@section('content')
    <x-page-header title="Peminahan" subtitle="Gejala birahi, inseminasi, dan pemijahan">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('pregnancy-checks.index') }}" class="btn btn-sm">Pemeriksaan Kehamilan</a>
                <a href="{{ route('breeding-records.create') }}" class="btn btn-primary btn-sm">+ Catat Peminahan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($awaitingCheck > 0)
        <div class="mb-4">
            <x-alert-row tone="info" title="{{ $awaitingCheck }} catatan peminahan belum diperiksa"
                         description="Status hewan tetap menunggu pemeriksaan. Kehamilan tidak diasumsikan hanya dari peminahan."
                         :url="route('pregnancy-checks.index')" url-label="Periksa" />
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <select name="animal_id" class="select select-sm select bg-base-100">
                <option value="">Semua betina</option>
                @foreach ($animals as $id => $code)
                    <option value="{{ $id }}" @selected((int) ($filters['animal_id'] ?? 0) === $id)>{{ $code }}</option>
                @endforeach
            </select>
            <select name="record_type" class="select select-sm select bg-base-100">
                <option value="">Semua jenis</option>
                @foreach ($recordTypes as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['record_type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Kode semen…" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('breeding-records.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($records->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada catatan peminahan"
                              description="Catat gejala birahi dan peminahan untuk menyusun riwayat reproduksi."
                              action-label="Catat Peminahan" action-url="{{ route('breeding-records.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Betina</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Pejantan</th>
                            <th>Inseminator</th>
                            <th>Pemeriksaan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td><a href="{{ route('animals.show', $record->animal) }}" class="font-medium text-primary hover:underline">{{ $record->animal->animal_code }}</a></td>
                                <td>{{ $record->record_type->label() }}</td>
                                <td class="whitespace-nowrap">{{ $record->breeding_date->translatedFormat('d M Y') }}</td>
                                <td class="font-mono text-[11px]">{{ $record->sireLabel() }}</td>
                                <td class="text-base-content/65">{{ $record->inseminator ?? '—' }}</td>
                                <td>
                                    @if ($record->pregnancy_checks_count > 0)
                                        <a href="{{ route('pregnancy-checks.index', ['animal_id' => $record->animal_id]) }}" class="text-xs text-primary hover:underline">
                                            Sudah diperiksa
                                        </a>
                                    @else
                                        <span class="text-xs text-base-content/45">Belum diperiksa</span>
                                    @endif
                                </td>
                                <td class="text-right"><a href="{{ route('breeding-records.show', $record) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $records->links() }}</div>
        @endif
    </x-panel>
@endsection
