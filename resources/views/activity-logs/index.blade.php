@extends('layouts.app', ['title' => __('Activity log')])

@php
    // The action list arrives as a plain array of strings; the filter wants
    // value => label pairs. Built here rather than inline on the component.
    $actionOptions = collect($actions)->mapWithKeys(fn ($action) => [$action => $action]);
@endphp

@section('content')
    <x-page-header :title="__('Activity log')"
                   :subtitle="__('Who did what, when, and against which record.')" />

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search description…')" :reset="route('activity-logs.index')"
                      columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="action"
                :options="$actionOptions"
                :selected="$filters['action'] ?? ''"
                :placeholder="__('All activity types') "
                :label="__('Filter by activity type') " />

            <x-filter-select name="user_id"
                :options="$users"
                :selected="$filters['user_id'] ?? ''"
                :placeholder="__('All users') "
                :label="__('Filter by user') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
        </x-filter-bar>

        @if ($logs->isEmpty())
            <div class="p-4">
                <x-empty-state :title="__('No activity recorded yet')"
                               :description="__('Every important action in the operational and finance modules is logged here.')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Time') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Activity') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap text-base-content/70">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ $log->user_name ?? __('System') }}</td>
                                <td>
                                    <span class="text-meta badge badge-soft badge-ghost badge-sm font-mono">{{ $log->humanAction() }}</span>
                                </td>
                                <td class="max-w-md truncate" title="{{ $log->description }}">{{ $log->description }}</td>
                                <td class="text-meta font-mono text-base-content/60">{{ $log->reference ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $logs->links() }}</div>
        @endif
    </x-panel>
@endsection
