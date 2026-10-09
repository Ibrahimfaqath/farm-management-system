<x-panel title="Riwayat Aktivitas" description="Jejak perubahan penting pada data hewan ini." padding="p-0">
    @php
        $logs = \App\Models\ActivityLog::forSubject($animal)->latest()->limit(25)->get();
    @endphp
    @if ($logs->isEmpty())
        <p class="p-4 text-sm text-base-content/55">Belum ada aktivitas tercatat untuk hewan ini.</p>
    @else
        <ul class="divide-y divide-base-300">
            @foreach ($logs as $log)
                <li class="flex items-start gap-3 px-4 py-2.5">
                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-primary/60"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">{{ $log->description }}</p>
                        <p class="mt-0.5 text-[11px] text-base-content/50">
                            {{ $log->user_name ?? 'Sistem' }} · {{ $log->created_at->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>
                    <span class="badge badge-sm border border-base-300 bg-base-100 text-[10px]">{{ $log->humanAction() }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-panel>
