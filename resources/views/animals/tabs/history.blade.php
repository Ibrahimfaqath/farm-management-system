<x-panel :title="__('Activity trail')" :description="__('Important changes to this animal’s record.')" padding="p-0">
    @php
        $logs = \App\Models\ActivityLog::forSubject($animal)->latest()->limit(25)->get();
        $canViewTrail = auth()->user()->canAccessModule('reports');
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
                            @if ($canViewTrail && $log->user_id)
                                <a href="{{ route('activity-logs.index', ['user_id' => $log->user_id]) }}"
                                   class="link link-hover">
                                    {{ $log->user_name ?? __('System') }}
                                </a>
                            @else
                                {{ $log->user_name ?? __('System') }}
                            @endif
                            · {{ $log->created_at->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>
                    @if ($canViewTrail)
                        <a href="{{ route('activity-logs.index', ['action' => $log->action]) }}"
                           title="{{ __('Show only this activity type') }}">
                            <x-badge :label="$log->humanAction()" class="badge-ghost badge-sm" />
                        </a>
                    @else
                        <x-badge :label="$log->humanAction()" class="badge-ghost badge-sm" />
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-panel>