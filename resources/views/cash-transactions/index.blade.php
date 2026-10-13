@extends('layouts.app', ['title' => __('Cash flow')])

@section('content')
    <x-page-header :title="__('Cash flow')" :subtitle="__('Money that actually moves in and out.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <button type="button" class="btn btn-primary btn-sm" data-modal-open="cash-modal">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record cash transaction') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-stat :label="__('Cash balance')" :value="'Rp'.number_format($balance, 0, ',', '.')" tone="primary" />
        <x-stat :label="__('Cash in this period')" :value="'Rp'.number_format($summary['cash_in'], 0, ',', '.')" tone="success" />
        <x-stat :label="__('Cash out this period')" :value="'Rp'.number_format($summary['cash_out'], 0, ',', '.')" tone="warning" />
        <x-stat :label="__('Net cash flow')" :value="'Rp'.number_format($summary['net_cash'], 0, ',', '.')"
               :tone="$summary['net_cash'] >= 0 ? 'success' : 'error'" />
        <x-stat :label="__('Cash in vs revenue')"
               :value="$summary['revenue'] > 0 ? 'Rp'.number_format($summary['cash_in'] / $summary['revenue'] * 100, 0).'%' : '—'"
               :hint="__('cash in ÷ revenue')" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search description…')" :reset="route('cash-transactions.index')">
            <x-filter-select name="direction"
                :options="$directions"
                :selected="$filters['direction'] ?? ''"
                :placeholder="__('All directions') "
                :label="__('Filter by direction') " />

            <x-filter-select name="category"
                :options="$categories"
                :selected="$filters['category'] ?? ''"
                :placeholder="__('All categories') "
                :label="__('Filter by category') " />

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="input input-sm w-full bg-base-100" aria-label="{{ __('From date') }}">
        </x-filter-bar>

        @if ($transactions->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No cash transactions yet')"
                              :description="__('Cash transactions are created automatically from customer payments, supplier payments, expenses and other income.')">
                    <x-slot:action>
                        @if (auth()->user()->hasAbility('finance.manage'))
                            <button type="button" class="btn btn-primary btn-sm" data-modal-open="cash-modal">
                                {{ __('Record cash transaction') }}
                            </button>
                        @endif
                    </x-slot:action>
                </x-empty-state>
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
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Recorded by') }}</th>
                            <th class="num">{{ __('Cash in') }}</th>
                            <th class="num">{{ __('Cash out') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td class="text-meta font-mono">{{ $transaction->transaction_number }}</td>
                                <td class="whitespace-nowrap">{{ $transaction->transaction_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $transaction->category->label() }}</td>
                                <td class="max-w-xs truncate">{{ $transaction->description }}</td>
                                <td class="text-base-content/65">{{ $transaction->method->label() }}</td>
                                <td class="text-base-content/65">{{ $transaction->creator?->name ?? '—' }}</td>
                                <td class="num text-tone-success">
                                    {{ $transaction->direction->value === 'in' ? number_format((float) $transaction->amount, 0) : '—' }}
                                </td>
                                <td class="num text-tone-warning">
                                    {{ $transaction->direction->value === 'out' ? number_format((float) $transaction->amount, 0) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-base-300 p-3">{{ $transactions->links() }}</div>
        @endif
    </x-panel>

    @if (auth()->user()->hasAbility('finance.manage'))
        <x-modal id="cash-modal" :title="__('Record cash transaction')"
                 :description="__('Use the Expenses, Income, Customer payment or Supplier payment modules for automatic transactions. This form is for owner draws and other manual entries.')">
            <form method="POST" action="{{ route('cash-transactions.store') }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.input name="transaction_date" :label="__('Date')" type="date" :value="now()->toDateString()" required />
                    <x-form.select name="category" :label="__('Category')" required
                                   :options="[
                                       'owner_draw' => __('Owner draw'),
                                       'other_outflow' => __('Other outflow'),
                                       'loan' => __('Loan receipt'),
                                   ]" />
                    <x-form.money name="amount" :label="__('Amount')" :value="0" required />
                    <x-form.select name="method" :label="__('Method')" required
                                   :options="[
                                       'cash' => __('Cash'),
                                       'transfer' => __('Bank transfer'),
                                       'debit_card' => __('Debit card'),
                                       'ewallet' => __('E-wallet'),
                                   ]" />
                    <x-form.input name="reference_number" :label="__('Reference number')" />
                </div>
                <x-form.input name="description" :label="__('Description')" required
                              :placeholder="__('For example: owner draw for this month')" />
                <x-form.textarea name="notes" :label="__('Notes')" rows="2" />
                <x-form.actions>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Cancel') }}</button>
                    <x-form.submit>{{ __('Save') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection