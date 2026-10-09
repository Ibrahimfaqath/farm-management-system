@extends('layouts.app', ['title' => 'Rekam Kesehatan'])

@section('content')
    <x-page-header title="Rekam Kesehatan" subtitle="Catatan observasi, pemeriksaan, dan diagnosis oleh tenaga medis">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('health-records.create') }}" class="btn btn-primary btn-sm">+ Catat Rekam</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Rekam Terbuka" :value="$openCount" :tone="$openCount > 0 ? 'warning' : 'success'" />
        <x-stat label="Biaya Bulan Ini" :value="'Rp'.number_format($monthCost, 0, ',', '.')" />
        <x-stat label="Total Rekam" :value="$records->total()" />
        <x-stat label="Ternak Dalam Perawatan"
               :value="App\Models\HealthRecord::open()->distinct('animal_id')->count('animal_id')" tone="info" />
    </div>

    @if ($openCount > 0)
        <div class="mb-4">
            <x-alert-row tone="warning" title="{{ $openCount }} rekam kesehatan belum selesai"
                         description="Rekam terbuka berarti Ternak masih dalam pemantauan. Sistem tidak menyimpulkan penyakit dari perubahan produksi."
                         :url="route('health-records.index', ['open_only' => 1])" url-label="Tampilkan" />
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <select name="animal_id" class="select select-sm select bg-base-100">
                <option value="">Semua ternak</option>
                @foreach ($animals as $id => $code)
                    <option value="{{ $id }}" @selected((int) ($filters['animal_id'] ?? 0) === $id)>{{ $code }}</option>
                @endforeach
            </select>
            <select name="status" class="select select-sm select bg-base-100">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="severity" class="select select-sm select bg-base-100">
                <option value="">Semua tingkat</option>
                @foreach ($severities as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['severity'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('health-records.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($records->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada rekam kesehatan"
                              description="Catat observasi harian atau hasil pemeriksaan dokter hewan untuk menjaga jejak kesehatan."
                              action-label="Catat Rekam" action-url="{{ route('health-records.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Ternak</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Tingkat</th>
                            <th>Gejala</th>
                            <th>Diagnosis</th>
                            <th>Status</th>
                            <th>Dokter</th>
                            <th class="text-right">Biaya</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $record->animal) }}" class="font-medium text-primary hover:underline">{{ $record->animal->animal_code }}</a>
                                </td>
                                <td class="whitespace-nowrap">{{ $record->record_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $record->record_type->label() }}</td>
                                <td>
                                    @if ($record->severity)
                                        <x-badge :label="$record->severity->label()" :class="$record->severity->badgeClass()" />
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="max-w-xs truncate">{{ $record->symptoms ?? '—' }}</td>
                                <td class="max-w-xs truncate">{{ $record->diagnosis ?? '—' }}</td>
                                <td><x-badge :label="$record->status->label()" :class="$record->status->badgeClass()" /></td>
                                <td class="text-base-content/65">{{ $record->veterinarian?->name ?? '—' }}</td>
                                <td class="text-right"><x-money :amount="$record->cost ?? 0" /></td>
                                <td class="text-right"><a href="{{ route('health-records.show', $record) }}" class="btn btn-ghost btn-xs">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $records->links() }}</div>
        @endif
    </x-panel>
@endsection
