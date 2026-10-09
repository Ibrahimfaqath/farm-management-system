@extends('layouts.app', ['title' => 'Jejak Aktivitas'])

@section('content')
    <x-page-header title="Jejak Aktivitas" subtitle="Siapa melakukan apa, kapan, dan untuk dokumen mana" />

    <x-panel padding="p-0">
        <form method="GET" class="grid gap-2 border-b border-base-300 p-3 sm:grid-cols-2 lg:grid-cols-5">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari keterangan…" class="input input-sm input bg-base-100">
            <select name="action" class="select select-sm select bg-base-100">
                <option value="">Semua jenis aktivitas</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <select name="user_id" class="select select-sm select bg-base-100">
                <option value="">Semua pengguna</option>
                @foreach ($users as $id => $name)
                    <option value="{{ $id }}" @selected((int) ($filters['user_id'] ?? 0) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input input-sm input bg-base-100">
            <div class="flex gap-2">
                <button class="btn btn-neutral btn-sm">Terapkan</button>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>

        @if ($logs->isEmpty())
            <div class="p-4">
                <x-empty-state title="Belum ada aktivitas tercatat"
                              description="Setiap tindakan penting pada modul operasional dan keuangan tercatat di sini." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm table-zebra">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aktivitas</th>
                            <th>Keterangan</th>
                            <th>Referensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap text-base-content/70">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ $log->user_name ?? 'Sistem' }}</td>
                                <td><span class="badge badge-sm border border-base-300 bg-base-100 font-mono text-[10px]">{{ $log->humanAction() }}</span></td>
                                <td class="max-w-md">{{ $log->description }}</td>
                                <td class="font-mono text-[11px] text-base-content/60">{{ $log->reference ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $logs->links() }}</div>
        @endif
    </x-panel>
@endsection