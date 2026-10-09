@extends('layouts.app', ['title' => 'Peternakan'])

@section('content')
    <x-page-header title="Peternakan" subtitle="Unit usaha dan lokasi operasional">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('settings.manage'))
                <a href="{{ route('farms.create') }}" class="btn btn-primary btn-sm">+ Tambah Peternakan</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <form method="GET" class="flex flex-wrap gap-2 border-b border-base-300 p-3">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode…" class="input input-sm input w-full bg-base-100 sm:max-w-xs">
            <button class="btn btn-neutral btn-sm">Cari</button>
            @if (request('search'))
                <a href="{{ route('farms.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>

        @if ($farms->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada peternakan terdaftar"
                              description="Tambahkan unit peternakan sebelum mendaftarkan kandang dan hewan."
                              action-label="Tambah Peternakan" action-url="{{ route('farms.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Lokasi</th><th>Pengelola</th><th class="text-right">Kandang</th><th class="text-right">Ternak Aktif</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($farms as $farm)
                            <tr>
                                <td class="font-mono text-xs">{{ $farm->code }}</td>
                                <td>
                                    <a href="{{ route('farms.show', $farm) }}" class="font-medium text-primary hover:underline">{{ $farm->name }}</a>
                                </td>
                                <td class="text-base-content/70">{{ collect([$farm->village, $farm->district, $farm->regency])->filter()->implode(', ') ?: '—' }}</td>
                                <td class="text-base-content/70">{{ $farm->manager_name ?? '—' }}</td>
                                <td class="text-right tabular-nums">{{ $farm->barns_count }}</td>
                                <td class="text-right tabular-nums">{{ $farm->animals_count }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('farms.show', $farm) }}" class="btn btn-ghost btn-xs">Detail</a>
                                    @if (auth()->user()->hasAbility('settings.manage'))
                                        <a href="{{ route('farms.edit', $farm) }}" class="btn btn-ghost btn-xs">Ubah</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $farms->links() }}</div>
        @endif
    </x-panel>
@endsection
