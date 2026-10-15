@extends('layouts.app', ['title' => __('Other income')])

@section('content')
    <x-page-header :title="__('Other income')"
                   :subtitle="__('Income outside milk sales, which are recorded through invoices.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <a href="{{ route('incomes.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record income') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <x-alert-row tone="info"
                     :title="__('Milk sales are recorded through invoices')"
                     :description="__('Revenue from milk and other product sales is recorded in the Sales module so receivables and cash stay connected.')"
                     :url="route('sales-orders.index')" :url-label="__('Open sales')" />
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat :label="__('This month')" :value="'Rp'.number_format($monthTotal, 0, ',', '.')" tone="success" />
        <x-stat :label="__('All records')" :value="$incomes->total()" />
        <x-stat :label="__('Average')"
               :value="$incomes->count() > 0 ? 'Rp'.number_format($monthTotal / $incomes->count(), 0, ',', '.') : '—'" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search description…')" :reset="route('incomes.index')">
            <x-filter-select name="category"
                :options="$categories"
                :selected="$filters['category'] ?? ''"
                :placeholder="__('All categories') "
                :label="__('Filter by category') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
        </x-filter-bar>

        @if ($incomes->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No other income yet')"
                              :description="__('Examples: manure sales, calf sales, or government assistance.')"
                              :url="route('incomes.create')" :submit-label="__('Record income')" />
            </div>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th class="num">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($incomes as $income)
                            <tr>
                                <td class="text-meta font-mono">{{ $income->income_number }}</td>
                                <td class="whitespace-nowrap">{{ $income->income_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $income->category->label() }}</td>
                                <td class="max-w-xs truncate">{{ $income->description }}</td>
                                <td class="text-base-content/65">{{ $income->source_name ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $income->payment_method->label() }}</td>
                                <td class="num font-medium text-tone-success"><x-money :amount="$income->amount" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $incomes->links() }}</div>
        @endif
    </x-panel>
@endsection