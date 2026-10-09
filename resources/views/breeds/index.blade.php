@extends('layouts.app', ['title' => 'Ras'])

@section('content')
    <x-page-header title="Ras" subtitle="Referensi ras hewan beserta parameter produktivitas">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('breeds.create') }}" class="btn btn-primary btn-sm">+ Tambah Ras</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-3">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode…" class="input input-sm input bg-base-100">
            <select name="species" class="select select-sm select bg-base-100">
                <option value="">Semua jenis hewan</option>
                @foreach ($speciesOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('species') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Cari</button>
                <a href="{{ route('breeds.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($breeds->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada ras terdaftar" description="Tambahkan ras agar hewan dapat diklasifikasikan sejak awal."
                              action-label="Tambah Ras" action-url="{{ route('breeds.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Jenis</th><th>Asal</th><th class="text-right">Prod. Susu Rata-rata</th><th class="text-right">Laktasi</th><th class="text-right">Berat Dewasa</th><th class="text-right">Ternak</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($breeds as $breed)
                            <tr>
                                <td class="font-mono text-xs">{{ $breed->code }}</td>
                                <td>
                                    <span class="font-medium">{{ $breed->name }}</span>
                                    @unless ($breed->is_active)
                                        <x-badge label="Nonaktif" class="badge-ghost" />
                                    @endunless
                                </td>
                                <td>{{ $breed->species }}</td>
                                <td class="text-base-content/70">{{ $breed->origin ?? '—' }}</td>
                                <td class="text-right tabular-nums">{{ $breed->average_milk_liters ? $breed->average_milk_liters.' L/hari' : '—' }}</td>
                                <td class="text-right tabular-nums">{{ $breed->average_lactation_days ? $breed->average_lactation_days.' hr' : '—' }}</td>
                                <td class="text-right tabular-nums">{{ $breed->adult_weight_kg ? $breed->adult_weight_kg.' kg' : '—' }}</td>
                                <td class="text-right tabular-nums">{{ $breed->animals_count }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('breeds.edit', $breed) }}" class="btn btn-ghost btn-xs">Ubah</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $breeds->links() }}</div>
        @endif
    </x-panel>
@endsection
