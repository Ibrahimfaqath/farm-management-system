@extends('layouts.app', ['title' => 'Kandang'])

@section('content')
    <x-page-header title="Kandang" subtitle="Daftar kandang dan lokasi hewan">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('barns.create') }}" class="btn btn-primary btn-sm">+ Tambah Kandang</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-3">
            <select name="farm_id" class="select select-sm select bg-base-100">
                <option value="">Semua peternakan</option>
                @foreach ($farms as $id => $name)
                    <option value="{{ $id }}" @selected((int) request('farm_id') === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="type" class="select select-sm select bg-base-100">
                <option value="">Semua jenis</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('barns.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($barns->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada kandang" description="Tambahkan kandang agar penempatan hewan dan lokasi stok tercatat jelas."
                              action-label="Tambah Kandang" action-url="{{ route('barns.create') }}" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead><tr><th>Kode</th><th>Nama</th><th>Peternakan</th><th>Jenis</th><th class="text-right">Isi</th><th class="text-right">Kapasitas</th><th class="text-right">Isi %</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($barns as $barn)
                            <tr>
                                <td class="font-mono text-xs">{{ $barn->code }}</td>
                                <td><a href="{{ route('barns.show', $barn) }}" class="font-medium text-primary hover:underline">{{ $barn->name }}</a></td>
                                <td class="text-base-content/70">{{ $barn->farm->name }}</td>
                                <td>{{ $barn->type->label() }}</td>
                                <td class="text-right tabular-nums">{{ $barn->animals_count }}</td>
                                <td class="text-right tabular-nums">{{ $barn->capacity ?? '—' }}</td>
                                <td class="text-right tabular-nums">
                                    @php $pct = $barn->occupancyPercentage(); @endphp
                                    @if ($pct === null) <span class="text-base-content/45">—</span>
                                    @else <span @class(['text-error' => $pct > 100, 'text-warning' => $pct >= 85 && $pct <= 100])>{{ $pct }}%</span> @endif
                                </td>
                                <td>
                                    <x-badge :label="$barn->is_active ? 'Aktif' : 'Nonaktif'"
                                             :class="$barn->is_active ? 'badge-soft badge-success' : 'badge-ghost'" />
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('barns.show', $barn) }}" class="btn btn-ghost btn-xs">Detail</a>
                                    @if (auth()->user()->hasAbility('animal.manage'))
                                        <a href="{{ route('barns.edit', $barn) }}" class="btn btn-ghost btn-xs">Ubah</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $barns->links() }}</div>
        @endif
    </x-panel>
@endsection
