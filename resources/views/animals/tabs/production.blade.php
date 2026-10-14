@php
    $period = request()->query('days', 30);
    $from = now()->subDays((int) $period);
    $rows = $animal->milkProductions()
        ->where('production_date', '>=', $from)
        ->orderBy('production_date')
        ->get();
    $total = (float) $rows->sum('liters');
    $peak = $rows->max('liters') ?? 0;
@endphp

<div class="space-y-4">
    <x-panel :title="__('Milk production')" padding="p-0">
        <x-slot:actions>
            <div class="join" role="group" aria-label="{{ __('Period') }}">
                @foreach ([7 => __('7 days'), 30 => __('30 days'), 90 => __('90 days')] as $days => $label)
                    <a href="?tab=produksi&days={{ $days }}"
                       @class(['btn join-item btn-xs', 'btn-neutral' => (int) $period === $days, 'btn-ghost' => (int) $period !== $days])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            @if (auth()->user()->hasAbility('production.record'))
                <a href="{{ route('milk-productions.create', ['animal_id' => $animal->id]) }}" class="btn btn-primary btn-xs">
                    <x-icon name="plus" :size="14" />
                    {{ __('Add') }}
                </a>
            @endif
        </x-slot:actions>

        @if ($rows->isEmpty())
            <p class="p-4 text-sm text-base-content/55">
                {{ __('No milk production recorded in the last :days days.', ['days' => $period]) }}
            </p>
        @else
            <div class="grid grid-cols-3 gap-3 border-b border-base-300 p-4">
                <div>
                    <p class="text-label text-base-content/50">{{ __('Total') }}</p>
                    <p class="text-lg font-semibold tabular-nums">{{ number_format($total, 1) }} L</p>
                </div>
                <div>
                    <p class="text-label text-base-content/50">{{ __('Average') }}</p>
                    <p class="text-lg font-semibold tabular-nums">{{ number_format($rows->avg('liters'), 1) }} L</p>
                </div>
                <div>
                    <p class="text-label text-base-content/50">{{ __('Highest') }}</p>
                    <p class="text-lg font-semibold tabular-nums">{{ number_format($peak, 1) }} L</p>
                </div>
            </div>

            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Session') }}</th>
                            <th class="num">{{ __('Liters') }}</th>
                            <th class="num">{{ __('Fat %') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($rows->reverse() as $row)
                            <tr>
                                <td class="whitespace-nowrap">{{ $row->production_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $row->session->label() }}</td>
                                <td class="num">{{ number_format((float) $row->liters, 1) }}</td>
                                <td class="num">{{ $row->fat_percent ?? '—' }}</td>
                                <td class="max-w-xs truncate text-base-content/60">{{ $row->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
</div>