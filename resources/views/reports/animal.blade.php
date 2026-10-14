@extends('layouts.app', ['title' => __('Farm report')])

@section('content')
    <x-page-header :title="__('Farm report')" :subtitle="__('Population, statuses, breeds, barns and production.')" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
        <form method="GET" class="flex items-center gap-2">
            <x-filter-select name="farm_id"
                :options="$farms"
                :selected="$filters['farm_id'] ?? ''"
                :placeholder="__('All farms') "
                :label="__('Filter by farm') " />
            <x-form.submit size="sm">{{ __('Apply') }}</x-form.submit>
            @if ($farmId)
                <a href="{{ route('reports.animal.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear filters') }}</a>
            @endif
        </form>

        <button type="button" class="btn btn-sm" onclick="window.print()">
            <x-icon name="print" :size="16" />
            {{ __('Print') }}
        </button>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ($statusOptions as $value => $label)
            <x-stat :label="$label" :value="$byStatus[$value] ?? 0"
                   :tone="match ($value) {
                       'active' => 'primary',
                       'deceased' => 'error',
                       'sold' => 'success',
                       default => 'neutral',
                   }" />
        @endforeach
    </div>

    <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <x-panel :title="__('Production status')" :description="__('Active animals only')">
            @if ($byProduction->isEmpty())
                <p class="text-sm text-base-content/55">{{ __('No active animals yet.') }}</p>
            @else
                @php $max = (int) $byProduction->max(); @endphp
                <ul class="space-y-2">
                    @foreach ($byProduction as $key => $total)
                        <li>
                            <div class="flex justify-between text-sm">
                                <span>{{ App\Enums\ProductionStatus::from($key)->label() }}</span>
                                <span class="tabular-nums">{{ $total }}</span>
                            </div>
                            <progress class="progress progress-primary mt-1 h-1.5 w-full" max="100"
                                      value="{{ $max > 0 ? $total / $max * 100 : 0 }}"></progress>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel :title="__('Breeding status')" :description="__('Active animals only')">
            @if ($byBreeding->isEmpty())
                <p class="text-sm text-base-content/55">{{ __('No active animals yet.') }}</p>
            @else
                @php $max = (int) $byBreeding->max(); @endphp
                <ul class="space-y-2">
                    @foreach ($byBreeding as $key => $total)
                        <li>
                            <div class="flex justify-between text-sm">
                                <span>{{ App\Enums\BreedingStatus::from($key)->label() }}</span>
                                <span class="tabular-nums">{{ $total }}</span>
                            </div>
                            <progress class="progress progress-primary mt-1 h-1.5 w-full" max="100"
                                      value="{{ $max > 0 ? $total / $max * 100 : 0 }}"></progress>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel :title="__('Age distribution')" :description="__('Active animals')">
            @if ($ageBuckets === [])
                <p class="text-sm text-base-content/55">{{ __('No age data yet.') }}</p>
            @else
                <ul class="space-y-1.5 text-sm">
                    @foreach ($ageBuckets as $bucket => $total)
                        <li class="flex justify-between gap-3">
                            <span class="text-base-content/70">{{ $bucket }}</span>
                            <span class="font-medium tabular-nums">{{ $total }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-panel :title="__('Breed composition')" padding="p-0">
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Breed') }}</th>
                            <th class="num">{{ __('Livestock') }}</th>
                            <th class="num">{{ __('Avg. milk') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($byBreed as $breed)
                            <tr>
                                <td class="font-medium">{{ $breed->name }}</td>
                                <td class="num">{{ $breed->animals_count }}</td>
                                <td class="num text-base-content/65">{{ $breed->average_milk_liters ? $breed->average_milk_liters.' L' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>

        <x-panel :title="__('Barn distribution')" padding="p-0">
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Barn') }}</th>
                            <th>{{ __('Farm') }}</th>
                            <th class="num">{{ __('Occupancy') }}</th>
                            <th class="num">{{ __('Capacity') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($byBarn as $barn)
                            <tr>
                                <td>
                                    <a href="{{ route('barns.show', $barn) }}" class="link link-hover font-medium text-primary">
                                        {{ $barn->name }}
                                    </a>
                                </td>
                                <td class="text-base-content/70">{{ $barn->farm->name }}</td>
                                <td class="num">{{ $barn->animals_count }}</td>
                                <td class="num text-base-content/65">{{ $barn->capacity ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    </div>

    <div class="mt-4">
        <x-panel :title="__('Production per animal')"
                 :description="__('Period :from – :to', [
                     'from' => $window['from']->translatedFormat('d M Y'),
                     'to' => $window['to']->translatedFormat('d M Y'),
                 ])"
                 padding="p-0">
            @if ($topProducers->isEmpty())
                <p class="p-4 text-sm text-base-content/55">{{ __('No production in this period.') }}</p>
            @else
                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Barn') }}</th>
                                <th class="num">{{ __('Total liters') }}</th>
                                <th class="num">{{ __('Daily average') }}</th>
                            </tr>
                        </thead>
                        <tbody class="motion-rows">
                            @foreach ($topProducers as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('animals.show', $row['animal']) }}" class="link link-hover font-medium text-primary">
                                            {{ $row['animal']->animal_code }}
                                        </a>
                                    </td>
                                    <td>{{ $row['animal']->name ?: '—' }}</td>
                                    <td class="text-base-content/70">{{ $row['animal']->barn?->name ?? '—' }}</td>
                                    <td class="num">{{ number_format($row['total'], 2) }}</td>
                                    <td class="num">{{ number_format($row['average'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>
    </div>
@endsection