@extends('layouts.app', ['title' => __('Milk production')])

@section('content')
    <x-page-header :title="__('Milk production')" :subtitle="__('Daily records per head or per barn group.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('production.record'))
                <a href="{{ route('milk-productions.batch') }}" class="btn btn-sm">{{ __('Enter per barn') }}</a>
                <a href="{{ route('milk-productions.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record production') }}
                </a>
            @endif
            <a href="{{ route('reports.production.index') }}" class="btn btn-sm">{{ __('Production report') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat :label="__('Total for period')" :value="\App\Support\Number::format($summary['total'], 1).' L'" tone="primary" />
        <x-stat :label="__('Daily average')" :value="\App\Support\Number::format($summary['per_day'], 1).' L'" />
        <x-stat :label="__('Per lactating head')" :value="\App\Support\Number::format($summary['per_lactating_head'], 1).' L'" />
        <x-stat :label="__('Records')" :value="$summary['records']" />
        <x-stat :label="__('Compared to previous')"
               :value="$trend['change_pct'] === null ? '—' : ($trend['change_pct'] > 0 ? '+' : '').$trend['change_pct'].'%'"
               :tone="match ($trend['direction']) { 'up' => 'success', 'down' => 'warning', default => 'neutral' }"
               :hint="__('daily average vs the previous period')" />
    </div>

    @if ($trend['direction'] === 'down' && $trend['change_pct'] !== null)
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="__('Production is down :pct% versus the previous period', ['pct' => abs($trend['change_pct'])])"
                         :description="__('This is a fact about the numbers, not a diagnosis. The system does not infer a cause.')" />
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('milk-productions.index')" :with-search="false"
                      columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="farm_id"
                :options="$farms"
                :selected="$filters['farm_id'] ?? ''"
                :placeholder="__('All farms') "
                :label="__('Filter by farm') " />

            <x-filter-select name="barn_id"
                :options="$barns"
                :selected="$filters['barn_id'] ?? ''"
                :placeholder="__('All barns') "
                :label="__('Filter by barn') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('To date') }}">
        </x-filter-bar>

        @if ($productions->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No production records yet')"
                              :description="__('Record daily milk per head or per barn group to monitor production performance.')"
                              :url="route('milk-productions.create')" :submit-label="__('Record production')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th>{{ __('Session') }}</th>
                            <th class="num">{{ __('Liters') }}</th>
                            <th class="num">{{ __('Fat %') }}</th>
                            <th class="num">{{ __('Protein %') }}</th>
                            <th>{{ __('Recorded by') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($productions as $production)
                            <tr>
                                <td class="whitespace-nowrap">{{ $production->production_date->translatedFormat('d M Y') }}</td>
                                <td>
                                    @if ($production->animal)
                                        <a href="{{ route('animals.show', $production->animal) }}" class="link link-hover font-medium text-primary">
                                            {{ $production->animal->animal_code }}
                                        </a>
                                    @else
                                        {{ $production->barn?->name ?? '—' }}
                                    @endif
                                </td>
                                <td>{{ $production->session->label() }}</td>
                                <td class="num font-medium">{{ \App\Support\Number::format((float) $production->liters, 2) }}</td>
                                <td class="num text-base-content/65">{{ $production->fat_percent ?? '—' }}</td>
                                <td class="num text-base-content/65">{{ $production->protein_percent ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $production->recorder?->name ?? '—' }}</td>
                                <td class="max-w-xs truncate text-base-content/60">{{ $production->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $productions->links() }}</div>
        @endif
    </x-panel>
@endsection