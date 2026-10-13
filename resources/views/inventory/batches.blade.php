@extends('layouts.app', ['title' => __('Feed batches')])

@section('content')
    <x-page-header :title="__('Feed batches')" :subtitle="__('Trace stock back to the receipt it came from.')"
                   :back="route('inventory.index')" />

    <x-panel padding="p-0">
        <x-filter-bar :reset="route('inventory.batches')" :with-search="false" columns="sm:grid-cols-3">
            <x-filter-select name="feed_item_id"
                :options="$feedItems"
                :selected="$filters['feed_item_id'] ?? ''"
                :placeholder="__('All feed items') "
                :label="__('Filter by feed item') " />

            <x-filter-select name="status"
                :options="$statuses"
                :selected="$filters['status'] ?? ''"
                :placeholder="__('All statuses') "
                :label="__('Filter by status') " />
        </x-filter-bar>

        @if ($batches->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No batches yet')"
                              :description="__('Batches are created automatically when a goods receipt is confirmed.')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Batch code') }}</th>
                            <th>{{ __('Feed item') }}</th>
                            <th>{{ __('Received') }}</th>
                            <th class="num">{{ __('Opening') }}</th>
                            <th class="num">{{ __('Remaining') }}</th>
                            <th class="num">{{ __('Used') }}</th>
                            <th>{{ __('Expiry') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            @php
                                $used = (float) $batch->original_quantity - (float) $batch->quantity_remaining;
                            @endphp
                            <tr>
                                <td class="font-mono text-xs">{{ $batch->batch_code }}</td>
                                <td class="font-medium">{{ $batch->feedItem?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $batch->received_date->translatedFormat('d M Y') }}</td>
                                <td class="num">{{ number_format((float) $batch->original_quantity, 2) }}</td>
                                <td class="num font-medium">{{ number_format((float) $batch->quantity_remaining, 2) }}</td>
                                <td class="num text-base-content/65">{{ number_format($used, 2) }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($batch->expiry_date)
                                        {{ $batch->expiry_date->translatedFormat('d M Y') }}
                                        @if ($batch->isExpired())
                                            <x-badge :label="__('Expired')" class="badge-soft badge-error" />
                                        @endif
                                    @else
                                        <span class="text-base-content/45">—</span>
                                    @endif
                                </td>
                                <td class="text-base-content/65">{{ $batch->storage_location ?? '—' }}</td>
                                <td>
                                    <x-badge :label="$batch->status->label()"
                                             :class="match ($batch->status->value) {
                                                 'active' => 'badge-soft badge-success',
                                                 'depleted' => 'badge-ghost',
                                                 'expired' => 'badge-soft badge-error',
                                                 default => 'badge-soft badge-warning',
                                             }" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $batches->links() }}</div>
        @endif
    </x-panel>
@endsection