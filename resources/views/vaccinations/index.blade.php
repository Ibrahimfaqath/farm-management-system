@extends('layouts.app', ['title' => __('Vaccinations')])

@section('content')
    <x-page-header :title="__('Vaccinations')" :subtitle="__('Administration history and the next due dates.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('vaccinations.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record vaccination') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Overdue')" :value="$overdueCount" :tone="$overdueCount > 0 ? 'error' : 'success'" />
        <x-stat :label="__('Due within 30 days')" :value="$dueSoonCount" :tone="$dueSoonCount > 0 ? 'warning' : 'success'" />
        <x-stat :label="__('All records')" :value="$vaccinations->total()" />
        <x-stat :label="__('Vaccines used')" :value="$vaccinations->pluck('vaccine_name')->unique()->count()" />
    </div>

    @if ($overdueCount > 0)
        <div class="mb-4">
            <x-alert-row tone="error"
                         :title="trans_choice(':count vaccination record is past its schedule|:count vaccination records are past their schedule', $overdueCount, ['count' => $overdueCount])"
                         :description="__('An overdue vaccination needs to be rescheduled.')"
                         :url="route('vaccinations.index', ['overdue' => 1])" :url-label="__('Show them')" />
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('vaccinations.index')" :with-search="false"
                      columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="animal_id"
                :options="$animals"
                :selected="$filters['animal_id'] ?? ''"
                :placeholder="__('All animals') "
                :label="__('Filter by animal') " />

            <input type="search" name="vaccine_name" value="{{ $filters['vaccine_name'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" :placeholder="__('Vaccine name…')"
                   aria-label="{{ __('Vaccine name') }}">

            <div class="flex flex-wrap items-center gap-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="checkbox" name="due" value="1" @checked($filters['due'] ?? false)
                           class="checkbox checkbox-sm checkbox-primary">
                    {{ __('Next 30 days') }}
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] ?? false)
                           class="checkbox checkbox-sm checkbox-primary">
                    {{ __('Overdue only') }}
                </label>
            </div>
        </x-filter-bar>

        @if ($vaccinations->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No vaccination records yet')"
                              :description="__('Record each administration so the next schedule stays easy to remember.')"
                              :url="route('vaccinations.create')" :submit-label="__('Record vaccination')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Animal') }}</th>
                            <th>{{ __('Vaccine') }}</th>
                            <th>{{ __('Batch') }}</th>
                            <th>{{ __('Administered') }}</th>
                            <th>{{ __('Next due') }}</th>
                            <th>{{ __('Dose') }}</th>
                            <th>{{ __('Veterinarian / officer') }}</th>
                            <th class="num">{{ __('Cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vaccinations as $vaccination)
                            <tr>
                                <td>
                                    <a href="{{ route('animals.show', $vaccination->animal) }}" class="link link-hover font-medium text-primary">
                                        {{ $vaccination->animal->animal_code }}
                                    </a>
                                </td>
                                <td class="font-medium">{{ $vaccination->vaccine_name }}</td>
                                <td class="text-meta font-mono text-base-content/65">{{ $vaccination->batch_number ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $vaccination->administered_date->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($vaccination->next_due_date)
                                        {{ $vaccination->next_due_date->translatedFormat('d M Y') }}
                                        @if ($vaccination->isOverdue())
                                            <x-badge :label="__('Overdue')" class="badge-soft badge-error" />
                                        @elseif ($vaccination->next_due_date->lte(now()->addDays(30)))
                                            <x-badge :label="__('Soon')" class="badge-soft badge-warning" />
                                        @endif
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="text-base-content/65">{{ $vaccination->dose ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $vaccination->veterinarian?->name ?? '—' }}</td>
                                <td class="num"><x-money :amount="$vaccination->cost ?? 0" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $vaccinations->links() }}</div>
        @endif
    </x-panel>
@endsection