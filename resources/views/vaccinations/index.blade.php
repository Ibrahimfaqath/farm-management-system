@extends('layouts.app', ['title' => 'Vaksinasi'])

@section('content')
    <x-page-header title="Vaksinasi" subtitle="Riwayat pemberian vaksin dan jadwal jatuh tempo">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('vaccinations.create') }}" class="btn btn-primary btn-sm">+ Catat Vaksinasi</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Lewat Jatuh Tempo" :value="$overdueCount" :tone="$overdueCount > 0 ? 'error' : 'success'" />
        <x-stat label="Jatuh Tempo 30 Hari" :value="$dueSoonCount" :tone="$dueSoonCount > 0 ? 'warning' : 'success'" />
        <x-stat label="Total Catatan" :value="$vaccinations->total()" />
        <x-stat label="Jenis Vaksin Aktif" :value="$vaccinations->pluck('vaccine_name')->unique()->count()" />
    </div>

    @if ($overdueCount > 0)
        <div class="mb-4">
            <x-alert-row tone="error" title="{{ $overdueCount }} catatan vaksinasi sudah lewat jadwal"
                         description="Vaksinasi yang lewat jatuh tempo perlu dijadwalkan ulang."
                         :url="route('vaccinations.index', ['overdue' => 1])" url-label="Tampilkan" />
        </div>
    @endif

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-4">
            <select name="animal_id" class="select select-sm select bg-base-100">
                <option value="">Semua ternak</option>
                @foreach ($animals as $id => $code)
                    <option value="{{ $id }}" @selected((int) ($filters['animal_id'] ?? 0) === $id)>{{ $code }}</option>
                @endforeach
            </select>
            <input name="vaccine_name" value="{{ $filters['vaccine_name'] ?? '' }}" placeholder="Nama vaksin…" class="input input-sm input bg-base-100">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="due" value="1" @checked($filters['due'] ?? false) class="checkbox checkbox-sm checkbox-primary">
                    30 hari ke depan
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] ?? false) class="checkbox checkbox-sm checkbox-primary">
                    Lewat tempo
                </label>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('vaccinations.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($vaccinations->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada catatan vaksinasi"
                              description="Catat pemberian vaksin agar jadwal berikutnya mudah diingat."
                              action-label="Catat vaksinasi" action-url="{{ route('vaccinations.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Ternak</th>
                            <th>Vaksin</th>
                            <th>Batch</th>
                            <th>Diberikan</th>
                            <th>Jatuh Tempo</th>
                            <th>Dosis</th>
                            <th>Dokter</th>
                            <th class="text-right">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vaccinations as $vaccination)
                            <tr>
                                <td><a href="{{ route('animals.show', $vaccination->animal) }}" class="font-medium text-primary hover:underline">{{ $vaccination->animal->animal_code }}</a></td>
                                <td class="font-medium">{{ $vaccination->vaccine_name }}</td>
                                <td class="font-mono text-[11px] text-base-content/65">{{ $vaccination->batch_number ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $vaccination->administered_date->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($vaccination->next_due_date)
                                        {{ $vaccination->next_due_date->translatedFormat('d M Y') }}
                                        @if ($vaccination->isOverdue())
                                            <x-badge label="Lewat" class="badge-soft badge-error" />
                                        @elseif ($vaccination->next_due_date->lte(now()->addDays(30)))
                                            <x-badge label="Segera" class="badge-soft badge-warning" />
                                        @endif
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="text-base-content/65">{{ $vaccination->dose ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $vaccination->veterinarian?->name ?? '—' }}</td>
                                <td class="text-right"><x-money :amount="$vaccination->cost ?? 0" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $vaccinations->links() }}</div>
        @endif
    </x-panel>
@endsection
