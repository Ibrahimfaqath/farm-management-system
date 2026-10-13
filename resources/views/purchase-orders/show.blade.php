@extends('layouts.app', ['title' => $purchaseOrder->po_number])

@section('content')
    <x-page-header :title="$purchaseOrder->po_number"
                   :subtitle="$purchaseOrder->supplier->name.' · '.($purchaseOrder->order_date?->translatedFormat('d M Y') ?? '—')"
                   :back="route('purchase-orders.index')"
                   :breadcrumbs="[
                       ['label' => __('Purchase orders'), 'url' => route('purchase-orders.index')],
                       ['label' => $purchaseOrder->po_number],
                   ]">
        <x-slot:actions>
            @if ($purchaseOrder->status->isEditable() && auth()->user()->hasAbility('feed.purchase'))
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-ghost btn-sm">
                    {{ __('Edit order') }}
                </a>

                <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}">
                    @csrf
                    <x-form.submit>{{ __('Approve order') }}</x-form.submit>
                </form>
            @endif

            @if (auth()->user()->hasAbility('feed.receive') && in_array($purchaseOrder->status->value, ['approved', 'partially_received'], true))
                <a href="{{ route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}" class="btn btn-primary btn-sm">
                    {{ __('Record receipt') }}
                </a>
            @endif

            @if ($purchaseOrder->status->value !== 'cancelled'
                 && $purchaseOrder->status->value !== 'received'
                 && ! $purchaseOrder->goodsReceipts->contains(fn ($receipt) => $receipt->status->value === 'posted')
                 && auth()->user()->hasAbility('feed.purchase'))
                <button type="button" class="btn btn-ghost btn-sm text-tone-error"
                        data-modal-open="cancel-order-modal">
                    {{ __('Cancel order') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($purchaseOrder->status->value === 'partially_received')
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="__('Partially received: :received of :ordered units', [
                             'received' => number_format($totalReceived, 2),
                             'ordered' => number_format($totalOrdered, 2),
                         ])"
                         :description="__('Outstanding: :outstanding units. The order stays open until it is fully received.', [
                             'outstanding' => number_format($totalOutstanding, 2),
                         ])" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Status')" :value="$purchaseOrder->status->label()"
               :tone="$purchaseOrder->status->value === 'received' ? 'success' : 'warning'" />
        <x-stat :label="__('Total ordered')" :value="number_format($totalOrdered, 2)" />
        <x-stat :label="__('Total received')" :value="number_format($totalReceived, 2)" tone="success" />
        <x-stat :label="__('Outstanding')" :value="number_format($totalOutstanding, 2)"
               :tone="$totalOutstanding > 0 ? 'error' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Order lines')" padding="p-0">
                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Feed item') }}</th>
                                <th class="num">{{ __('Ordered') }}</th>
                                <th class="num">{{ __('Received') }}</th>
                                <th class="num">{{ __('Outstanding') }}</th>
                                <th class="num">{{ __('Price') }}</th>
                                <th class="num">{{ __('Value') }}</th>
                                <th>{{ __('Progress') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $line)
                                <tr>
                                    <td>
                                        <p class="font-medium">{{ $line->item->feedItem->name }}</p>
                                        <p class="text-meta font-mono text-base-content/45">{{ $line->item->feedItem->code }}</p>
                                    </td>
                                    <td class="num">{{ number_format($line->ordered, 2) }} <span class="text-meta text-base-content/50">{{ $line->item->unit->shortLabel() }}</span></td>
                                    <td class="num text-tone-success">{{ number_format($line->received, 2) }}</td>
                                    <td class="num {{ $line->outstanding > 0 ? 'text-tone-error' : 'text-base-content/45' }}">
                                        {{ number_format($line->outstanding, 2) }}
                                    </td>
                                    <td class="num"><x-money :amount="$line->item->unit_price" /></td>
                                    <td class="num"><x-money :amount="$line->item->line_total" /></td>
                                    <td class="w-32">
                                        <progress class="progress h-1.5 w-full {{ $line->received_pct >= 100 ? 'progress-success' : 'progress-warning' }}"
                                                  max="100" value="{{ min(100, $line->received_pct) }}"></progress>
                                        <span class="text-meta text-base-content/50">{{ $line->received_pct }} %</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-base-300 p-3">
                    <dl class="ml-auto w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-base-content/55">{{ __('Subtotal') }}</dt>
                            <dd><x-money :amount="$purchaseOrder->subtotal" /></dd>
                        </div>
                        @if ((float) $purchaseOrder->discount_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-base-content/55">{{ __('Discount') }}</dt>
                                <dd>&minus;<x-money :amount="$purchaseOrder->discount_amount" /></dd>
                            </div>
                        @endif
                        @if ((float) $purchaseOrder->tax_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-base-content/55">{{ __('Tax') }}</dt>
                                <dd><x-money :amount="$purchaseOrder->tax_amount" /></dd>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-base-300 pt-1 font-medium">
                            <dt>{{ __('Total') }}</dt>
                            <dd><x-money :amount="$purchaseOrder->total" /></dd>
                        </div>
                    </dl>
                </div>
            </x-panel>

            <x-panel :title="__('Receipt history')" padding="p-0">
                @if ($purchaseOrder->goodsReceipts->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No goods receipts for this order yet.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="num">{{ __('Value') }}</th>
                                    <th class="row-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseOrder->goodsReceipts as $receipt)
                                    <tr>
                                        <td class="font-mono text-xs">{{ $receipt->receipt_number }}</td>
                                        <td class="whitespace-nowrap">{{ $receipt->received_date->translatedFormat('d M Y') }}</td>
                                        <td><x-badge :label="$receipt->status->label()" :class="$receipt->status->badgeClass()" /></td>
                                        <td class="num"><x-money :amount="$receipt->total_amount" /></td>
                                        <td class="row-actions">
                                            <x-actions-menu :label="__('Actions for receipt :number', ['number' => $receipt->receipt_number])">
                                                    <x-menu-link :href="route('goods-receipts.show', $receipt)" icon="eye">
                                                        {{ __('Details') }}
                                                    </x-menu-link>
                                                </x-actions-menu>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Order information')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Supplier') }}</dt>
                    <dd class="text-right">{{ $purchaseOrder->supplier->name }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Terms') }}</dt>
                    <dd class="text-right">{{ $purchaseOrder->supplier->payment_terms_days }} {{ __('days') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Order date') }}</dt>
                    <dd class="text-right">{{ $purchaseOrder->order_date?->translatedFormat('d M Y') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Expected arrival') }}</dt>
                    <dd class="text-right">{{ $purchaseOrder->expected_date?->translatedFormat('d M Y') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Created by') }}</dt>
                    <dd class="text-right">{{ $purchaseOrder->creator?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Approved by') }}</dt>
                    <dd class="text-right">{{ $purchaseOrder->approver?->name ?? '—' }}</dd>
                </div>
            </dl>
            @if ($purchaseOrder->notes)
                <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $purchaseOrder->notes }}</p>
            @endif
        </x-panel>
    </div>

    @if ($purchaseOrder->status->value !== 'cancelled'
         && $purchaseOrder->status->value !== 'received'
         && ! $purchaseOrder->goodsReceipts->contains(fn ($receipt) => $receipt->status->value === 'posted')
         && auth()->user()->hasAbility('feed.purchase'))
        <x-modal id="cancel-order-modal" :title="__('Cancel order :number?', ['number' => $purchaseOrder->po_number])"
                 :description="__('Nothing is deleted and no stock moves. The order is marked as cancelled so it stops showing as outstanding.')">
            <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" class="mt-4">
                @csrf
                <x-form.actions bare>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Keep order') }}</button>
                    <x-form.submit variant="error">{{ __('Cancel order') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection