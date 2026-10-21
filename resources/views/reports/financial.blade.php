@extends('layouts.app', ['title' => __('Financial report')])

@section('content')
    <x-page-header :title="__('Financial report')"
                   :subtitle="__('Revenue, expenses, receivables, payables and cash flow. Profit is not the same as cash.')" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="date_from" value="{{ $from }}"
                   class="input input-sm w-full bg-base-100 sm:w-44" aria-label="{{ __('From date') }}">
            <input type="date" name="date_to" value="{{ $to }}"
                   class="input input-sm w-full bg-base-100 sm:w-44" aria-label="{{ __('To date') }}">
            <x-form.submit size="sm">{{ __('Apply') }}</x-form.submit>
            <a href="{{ route('reports.financial.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear filters') }}</a>
        </form>

        <button type="button" class="btn btn-sm" onclick="window.print()">
            <x-icon name="print" :size="16" />
            {{ __('Print') }}
        </button>
    </div>

    <section class="mb-5">
        <x-section-title :title="__('Current financial position')" icon="chart" />
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat :label="__('Cash')" :value="'Rp'.number_format($cash, 0, ',', '.')" tone="info" />
            <x-stat :label="__('Receivable')" :value="'Rp'.number_format($receivable, 0, ',', '.')" tone="warning"
                   :hint="__('overdue :amount', ['amount' => 'Rp'.number_format($overdue, 0, ',', '.')])" />
            <x-stat :label="__('Payable')" :value="'Rp'.number_format($payable, 0, ',', '.')" tone="warning" />
            <x-stat :label="__('Net cash flow')" :value="'Rp'.number_format($summary['net_cash'], 0, ',', '.')"
                   :tone="$summary['net_cash'] >= 0 ? 'success' : 'error'" />
        </div>
    </section>

    <section class="mb-5">
        <x-section-title
            :title="__('Period :from – :to', [
                'from' => \Illuminate\Support\Carbon::parse($from)->translatedFormat('d M Y'),
                'to' => \Illuminate\Support\Carbon::parse($to)->translatedFormat('d M Y'),
            ])"
            icon="calendar" />
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat :label="__('Revenue')" :value="'Rp'.number_format($summary['revenue'], 0, ',', '.')" tone="success"
                   :hint="__('from invoices')" />
            <x-stat :label="__('Other income')" :value="'Rp'.number_format($summary['other_income'], 0, ',', '.')" tone="success"
                   :hint="__('outside invoices')" />
            <x-stat :label="__('Expenses')" :value="'Rp'.number_format($summary['operating_expense'], 0, ',', '.')" tone="warning" />
            <x-stat :label="__('Estimated profit')" :value="'Rp'.number_format($summary['profit_estimate'], 0, ',', '.')"
                   :tone="$summary['profit_estimate'] >= 0 ? 'primary' : 'error'"
                   :hint="__('revenue + other income − expenses')" />
        </div>
    </section>

    <div class="mb-4 grid gap-4 lg:grid-cols-2">
        <x-panel :title="__('Cash flow by month')" :description="__('Money that actually moved in and out.')">
            @if ($cashByMonth->isEmpty())
                <p class="text-sm text-base-content/55">{{ __('No cash transactions in this period.') }}</p>
            @else
                @php $max = max(1, (float) $cashByMonth->flatMap(fn ($flow) => [$flow['in'], $flow['out']])->max()); @endphp
                <ul class="space-y-3">
                    @foreach ($cashByMonth as $month => $flow)
                        <li>
                            <div class="flex justify-between gap-2 text-sm">
                                <span class="font-medium">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y') }}</span>
                                <span class="shrink-0 text-xs tabular-nums">
                                    <span class="text-tone-success">+<x-money :amount="$flow['in']" /></span>
                                    <span class="text-tone-warning">&minus;<x-money :amount="$flow['out']" /></span>
                                </span>
                            </div>
                            <div class="mt-1 flex gap-0.5">
                                <div class="h-2 rounded-l bg-success/70" style="width: {{ $flow['in'] / $max * 100 }}%"></div>
                                <div class="h-2 rounded-r bg-warning/70" style="width: {{ $flow['out'] / $max * 100 }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel :title="__('Expenses by category')" padding="p-0">
            @if ($expensesByCategory->isEmpty())
                <p class="p-4 text-sm text-base-content/55">{{ __('No expenses in this period.') }}</p>
            @else
                @php $totalExpense = (float) $expensesByCategory->sum('total'); @endphp
                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <th class="num">{{ __('Records') }}</th>
                                <th class="num">{{ __('Amount') }}</th>
                                <th class="num">{{ __('Share') }}</th>
                            </tr>
                        </thead>
                        <tbody class="motion-rows">
                            @foreach ($expensesByCategory as $row)
                                <tr>
                                    <td>{{ $expenseCategories[$row['category']] ?? $row['category'] }}</td>
                                    <td class="num">{{ $row['records'] }}</td>
                                    <td class="num"><x-money :amount="$row['total']" /></td>
                                    <td class="num text-base-content/60">
                                        {{ $totalExpense > 0 ? round($row['total'] / $totalExpense * 100, 1) : 0 }} %
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>
    </div>

    <x-panel :title="__('Revenue by customer')" padding="p-0"
             :description="__('Revenue is recognised when an invoice is issued; the paid column is the cash already received.')">
        @if ($revenueByCustomer->isEmpty())
            <p class="p-4 text-sm text-base-content/55">{{ __('No invoices in this period.') }}</p>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Customer') }}</th>
                            <th class="num">{{ __('Invoiced') }}</th>
                            <th class="num">{{ __('Paid') }}</th>
                            <th class="num">{{ __('Outstanding') }}</th>
                            <th class="num">{{ __('Collection rate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($revenueByCustomer as $row)
                            @php $unpaid = (float) $row['total'] - (float) $row['paid']; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('customers.show', $row['customer']) }}" class="link link-hover font-medium text-primary">
                                        {{ $row['customer']->name }}
                                    </a>
                                </td>
                                <td class="num"><x-money :amount="$row['total']" /></td>
                                <td class="num text-tone-success"><x-money :amount="$row['paid']" /></td>
                                <td class="num {{ $unpaid > 0 ? 'text-tone-warning' : 'text-tone-success' }}"><x-money :amount="$unpaid" /></td>
                                <td class="num">{{ (float) $row['total'] > 0 ? round($row['paid'] / $row['total'] * 100, 1) : 0 }} %</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
@endsection