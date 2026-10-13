<x-panel :title="__('Activity trail')" :description="__('Important changes to this animal’s record.')" padding="p-0">
    @php
        $logs = \App\Models\ActivityLog::forSubject($animal)->latest()->limit(25)->get();
    @endphp

    @if ($logs->isEmpty())
        <p class="p-4 text-sm text-base-content/55">{{ __('No activity recorded for this animal yet.') }}</p>
    @else
        <ul class="divide-y divide-base-300">
            @foreach ($logs as $log)
                <li class="flex items-start gap-3 px-4 py-2.5">
                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-primary/60"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">{{ $log->description }}</p>
                        <p class="text-meta mt-0.5 text-base-content/50">
                            {{ $log->user_name ?? __('System') }} · {{ $log->created_at->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>
                    <x-badge :label="$log->humanAction()" class="badge-ghost badge-sm" />
                </li>
            @endforeach
        </ul>
    @endif
</x-panel>