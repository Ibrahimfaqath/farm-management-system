@extends('layouts.app', ['title' => __('Expenses')])

@section('content')
    <x-page-header :title="__('Expenses')" :subtitle="__('Every expense is also recorded as cash out.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record expense') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Expenses this period')" :value="'Rp'.number_format($summary['operating_expense'], 0, ',', '.')" tone="warning" />
        <x-stat :label="__('Revenue this period')" :value="'Rp'.number_format($summary['revenue'], 0, ',', '.')" tone="success" />
        <x-stat :label="__('Cash out this period')" :value="'Rp'.number_format($summary['cash_out'], 0, ',', '.')" />
        <x-stat :label="__('Estimated profit')" :value="'Rp'.number_format($summary['profit_estimate'], 0, ',', '.')"
               :tone="$summary['profit_estimate'] >= 0 ? 'primary' : 'error'"
               :hint="__('revenue + other income − expenses')" />
    </div>

    <div class="grid gap-4">
        <div class="min-w-0">
            <x-panel padding="p-0">
                <x-filter-bar :search-placeholder="__('Search description…')" :reset="route('expenses.index')"
                              columns="sm:grid-cols-2 lg:grid-cols-4">
                    <x-filter-select name="category"
                        :options="$categories"
                        :selected="$filters['category'] ?? ''"
                        :placeholder="__('All categories') "
                        :label="__('Filter by category') " />

                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
                </x-filter-bar>

                @if ($expenses->isEmpty())
                    <div class="p-4">
                        <x-empty-state :label="__('No expenses recorded')"
                                      :description="__('Record operating costs such as feed, veterinary services and labour.')"
                                      :url="route('expenses.create')" :submit-label="__('Record expense')" />
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
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th class="num">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td class="text-meta font-mono">{{ $expense->expense_number }}</td>
                                        <td class="whitespace-nowrap">{{ $expense->expense_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $expense->category->label() }}</td>
                                        <td class="max-w-xs truncate">{{ $expense->description }}</td>
                                        <td class="max-w-xs truncate text-base-content/65"
                                            title="{{ $expense->supplier?->name ?? $expense->vendor_name ?? '—' }}">{{ $expense->supplier?->name ?? $expense->vendor_name ?? '—' }}</td>
                                        <td class="text-base-content/65">{{ $expense->payment_method->label() }}</td>
                                        <td class="num font-medium"><x-money :amount="$expense->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-base-300 p-3">{{ $expenses->links() }}</div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Expenses by category')" :description="__('Current period')">
            @if ($byCategory->isEmpty())
                <p class="text-sm text-base-content/55">{{ __('No expenses in this period yet.') }}</p>
            @else
                @php $maxTotal = (float) $byCategory->max('total'); @endphp
                <ul class="space-y-2.5">
                    @foreach ($byCategory as $row)
                        <li>
                            <div class="flex justify-between gap-2 text-sm">
                                <span class="truncate">{{ $categories[$row['category']] ?? $row['category'] }}</span>
                                <x-money :amount="$row['total']" class="tabular-nums" />
                            </div>
                            <progress class="progress progress-secondary mt-1 h-1.5 w-full" max="100"
                                      value="{{ $maxTotal > 0 ? ($row['total'] / $maxTotal) * 100 : 0 }}"></progress>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    </div>
@endsection