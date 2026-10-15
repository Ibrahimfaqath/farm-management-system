@extends('layouts.app', ['title' => __('Invoices')])

@section('content')
    <x-page-header :title="__('Invoices')"
                   :subtitle="__('Receivables grow when an invoice is issued; cash grows when a payment is received.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('payment.record'))
                <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Record payment') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Total receivable')" :value="'Rp'.number_format($receivable, 0, ',', '.')" tone="warning" />
        <x-stat :label="__('Overdue receivable')" :value="'Rp'.number_format($overdueAmount, 0, ',', '.')"
               :tone="$overdueAmount > 0 ? 'error' : 'success'" />
        <x-stat :label="__('Overdue invoices')" :value="$overdueCount"
               :tone="$overdueCount > 0 ? 'error' : 'success'" />
        <x-stat :label="__('All invoices')" :value="$invoices->total()" />
    </div>

    @if ($overdueCount > 0)
        <div class="mb-4">
            <x-alert-row tone="error"
                         :title="trans_choice(':count invoice is past its due date|:count invoices are past their due dates', $overdueCount, ['count' => $overdueCount])"
                         :description="__('Total overdue receivable: :amount. An invoice counts as overdue once its due date has passed and a balance remains.', ['amount' => 'Rp'.number_format($overdueAmount, 0)])"
                         :url="route('invoices.index', ['overdue' => 1])" :url-label="__('Show only overdue')" />
        </div>
    @endif

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search invoice number…')" :reset="route('invoices.index')">
            <x-filter-select name="status"
                :options="$statuses"
                :selected="$filters['status'] ?? ''"
                :placeholder="__('All statuses') "
                :label="__('Filter by status') " />

            <x-filter-select name="customer_id"
                :options="$customers"
                :selected="$filters['customer_id'] ?? ''"
                :placeholder="__('All customers') "
                :label="__('Filter by customer') " />

            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] ?? false)
                       class="checkbox checkbox-sm checkbox-primary">
                {{ __('Overdue only') }}
            </label>
        </x-filter-bar>

        @if ($invoices->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No invoices yet')"
                              :description="__('Invoices are created from sales orders. Issuing one raises revenue and receivables without moving cash.')"
                              :url="route('sales-orders.create')" :submit-label="__('Create sales order')" />
            </div>
        @else
            {{-- Below `lg` the nine columns become a list of cards. The invoice
                 number was the worst of it: at 375px "INV-20261015-0005" broke
                 across three lines as "INV-", "20261015-", "0005", so the one
                 thing you identify an invoice by was the hardest thing to read.
                 The card gives the number its own line, keeps the customer with
                 it, and puts the balance — the figure this list exists to show —
                 where it can be compared at a glance. --}}
            <x-record-cards :label="__('Invoice list')">
                @foreach ($invoices as $invoice)
                    <x-record-card :href="route('invoices.show', $invoice)"
                                   :title="$invoice->invoice_number.' '.__('Details')">
                        <p class="truncate font-mono text-sm font-medium text-primary">
                            {{ $invoice->invoice_number }}
                        </p>
                        <p class="mt-0.5 truncate text-meta text-base-content/60">
                            {{ $invoice->customer->name }} ·
                            {{ $invoice->invoice_date->translatedFormat('d M Y') }}
                        </p>

                        <p class="mt-1.5 flex flex-wrap items-center gap-1.5 text-meta">
                            <x-badge :label="$invoice->status->label()" :class="$invoice->status->badgeClass()" />
                            @if ($invoice->isOverdue())
                                <x-badge :label="$invoice->daysOverdue().' '.__('days late')" class="badge-soft badge-error" />
                            @endif
                            <span class="ml-auto shrink-0 font-semibold tabular-nums {{ $invoice->balance() > 0 ? 'text-tone-warning' : 'text-tone-success' }}">
                                <x-money :amount="$invoice->balance()" />
                            </span>
                        </p>

                        <x-slot:actions>
                            <x-actions-menu :label="__('Actions for :number', ['number' => $invoice->invoice_number])">
                                @if ($invoice->balance() > 0 && auth()->user()->hasAbility('payment.record'))
                                    <x-menu-link :href="route('payments.create', ['invoice_id' => $invoice->id])" icon="cash">
                                        {{ __('Pay') }}
                                    </x-menu-link>
                                @endif
                                <x-menu-link :href="route('invoices.show', $invoice)" icon="eye">
                                    {{ __('Details') }}
                                </x-menu-link>
                            </x-actions-menu>
                        </x-slot:actions>
                    </x-record-card>
                @endforeach
            </x-record-cards>

            <div class="table-scroll hidden lg:block">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Due date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Total') }}</th>
                            <th class="num">{{ __('Paid') }}</th>
                            <th class="num">{{ __('Balance') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="link link-hover font-mono text-xs text-primary">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>
                                <td>{{ $invoice->customer->name }}</td>
                                <td class="whitespace-nowrap">{{ $invoice->invoice_date->translatedFormat('d M Y') }}</td>
                                <td>
                                    {{-- The date itself must not break, but the
                                         overdue badge below it may. Holding the
                                         whole cell to one line made the badge
                                         set the width of the column: this
                                         column measured 225px for the sake of a
                                         "2 days late" tag, against 103px for a
                                         plain date, which at a 1280px viewport
                                         was the difference between the table
                                         fitting its panel and not. --}}
                                    <span class="whitespace-nowrap">{{ $invoice->due_date->translatedFormat('d M Y') }}</span>
                                    @if ($invoice->isOverdue())
                                        <x-badge :label="$invoice->daysOverdue().' '.__('days late')" class="badge-soft badge-error" />
                                    @endif
                                </td>
                                <td><x-badge :label="$invoice->status->label()" :class="$invoice->status->badgeClass()" /></td>
                                <td class="num"><x-money :amount="$invoice->total" /></td>
                                <td class="num text-base-content/65"><x-money :amount="$invoice->paid_amount" /></td>
                                <td class="num font-medium {{ $invoice->balance() > 0 ? 'text-tone-warning' : 'text-tone-success' }}">
                                    <x-money :amount="$invoice->balance()" />
                                </td>
                                    <td class="row-actions">
                                        <x-actions-menu :label="__('Actions for :number', ['number' => $invoice->invoice_number])">
                                            @if ($invoice->balance() > 0 && auth()->user()->hasAbility('payment.record'))
                                                <x-menu-link :href="route('payments.create', ['invoice_id' => $invoice->id])" icon="cash">
                                                    {{ __('Pay') }}
                                                </x-menu-link>
                                            @endif
                                            <x-menu-link :href="route('invoices.show', $invoice)" icon="eye">
                                                {{ __('Details') }}
                                            </x-menu-link>
                                        </x-actions-menu>
                                    </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$invoices" />
        @endif
    </x-panel>
@endsection