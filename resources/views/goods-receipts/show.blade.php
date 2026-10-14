@extends('layouts.app', ['title' => $receipt->receipt_number])

@section('content')
    <x-page-header :title="$receipt->receipt_number"
                   :subtitle="$receipt->supplier->name.' · '.$receipt->received_date->translatedFormat('d M Y')"
                   :back="route('goods-receipts.index')"
                   :breadcrumbs="[
                       ['label' => __('Goods receipts'), 'url' => route('goods-receipts.index')],
                       ['label' => $receipt->receipt_number],
                   ]">
        <x-slot:actions>
            @if ($receipt->status->value === 'draft' && auth()->user()->hasAbility('feed.receive'))
                <button type="button" class="btn btn-ghost btn-sm text-tone-error"
                        data-modal-open="cancel-receipt-modal">
                    {{ __('Cancel receipt') }}
                </button>

                <form method="POST" action="{{ route('goods-receipts.post', $receipt) }}">
                    @csrf
                    <x-form.submit>{{ __('Confirm and add to stock') }}</x-form.submit>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($receipt->status->value === 'draft')
        <div class="mb-4">
            <x-alert-row tone="warning"
                         :title="__('This receipt is still a draft')"
                         :description="__('Stock has not increased yet. Confirm the receipt so the batches are created and stock goes up.')" />
        </div>
    @elseif ($receipt->status->affectsInventory())
        <div class="mb-4">
            <x-alert-row tone="info"
                         :title="__('Received: :received units from the order', ['received' => number_format((float) $receipt->items->sum('quantity_received'), 2)])"
                         :description="__('Stock increased by :received units worth :value. Batches were created automatically.', [
                             'received' => number_format((float) $receipt->items->sum('quantity_received'), 2),
                             'value' => 'Rp'.number_format((float) $receipt->total_amount, 0),
                         ])" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Status')" :value="$receipt->status->label()"
               :tone="$receipt->status->affectsInventory() ? 'success' : 'warning'" />
        <x-stat :label="__('Receipt value')" :value="'Rp'.number_format($totalAmount, 0, ',', '.')" />
        <x-stat :label="__('Paid')" :value="'Rp'.number_format($paidAmount, 0, ',', '.')" tone="info" />
        <x-stat :label="__('Outstanding payable')" :value="'Rp'.number_format($outstanding, 0, ',', '.')"
               :tone="$outstanding > 0 ? 'warning' : 'success'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Received items')" padding="p-0">
                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Feed item') }}</th>
                                <th class="num">{{ __('Received') }}</th>
                                <th class="num">{{ __('Price') }}</th>
                                <th class="num">{{ __('Line total') }}</th>
                                <th>{{ __('Batch') }}</th>
                                <th>{{ __('Expiry') }}</th>
                            </tr>
                        </thead>
                        <tbody class="motion-rows">
                            @foreach ($receipt->items as $item)
                                <tr>
                                    <td>
                                        <p class="font-medium">{{ $item->feedItem->name }}</p>
                                        <p class="text-meta font-mono text-base-content/45">{{ $item->feedItem->code }}</p>
                                    </td>
                                    <td class="num">{{ number_format((float) $item->quantity_received, 2) }} <span class="text-meta text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                    <td class="num"><x-money :amount="$item->unit_price" /></td>
                                    <td class="num"><x-money :amount="$item->line_total" /></td>
                                    <td>
                                        @if ($item->batch)
                                            <span class="text-meta font-mono">{{ $item->batch->batch_code }}</span>
                                            <span class="text-meta block text-base-content/50">
                                                {{ __('remaining :qty', ['qty' => number_format((float) $item->batch->quantity_remaining, 2)]) }}
                                            </span>
                                        @else
                                            <span class="text-base-content/45">{{ $item->batch_number ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">{{ $item->expiry_date?->translatedFormat('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-base-300 p-3 text-right text-sm font-medium">
                    {{ __('Total') }} <x-money :amount="$totalAmount" />
                </div>
            </x-panel>

            <x-panel :title="__('Stock movements')" padding="p-0"
                     :description="__('The stock audit trail produced by this receipt.')">
                @if ($movements->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No stock movements yet, because this receipt is not confirmed.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Item') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th class="num">{{ __('Quantity') }}</th>
                                    <th>{{ __('Batch') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($movements as $movement)
                                    <tr>
                                        <td>{{ $movement->feedItem?->name }}</td>
                                        <td><x-badge :label="$movement->type->label()" :class="$movement->type->badgeClass()" /></td>
                                        <td class="num text-tone-success">+{{ number_format((float) $movement->quantity, 2) }}</td>
                                        <td class="text-meta font-mono">{{ $movement->batch?->batch_code ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $movement->movement_date->translatedFormat('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <div class="space-y-4">
            <x-panel :title="__('Information')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Supplier') }}</dt>
                        <dd class="text-right">{{ $receipt->supplier->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Purchase order') }}</dt>
                        <dd class="text-right">
                            @if ($receipt->purchaseOrder)
                                <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}" class="link link-hover font-mono text-primary">
                                    {{ $receipt->purchaseOrder->po_number }}
                                </a>
                            @else
                                <span class="text-base-content/45">{{ __('No purchase order') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Delivery note') }}</dt>
                        <dd class="text-right">{{ $receipt->delivery_note_reference ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Received by') }}</dt>
                        <dd class="text-right">{{ $receipt->receiver?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-base-content/55">{{ __('Confirmed') }}</dt>
                        <dd class="text-right">{{ $receipt->posted_at?->translatedFormat('d M Y H:i') ?? __('Not yet') }}</dd>
                    </div>
                </dl>
            </x-panel>

            @if ($receipt->quality_notes)
                <x-panel :title="__('Quality notes')">
                    <p class="text-xs leading-relaxed text-base-content/70">{{ $receipt->quality_notes }}</p>
                </x-panel>
            @endif

            @if ($receipt->payments->isNotEmpty())
                <x-panel :title="__('Supplier payments')" padding="p-0">
                    <ul class="divide-y divide-base-300">
                        @foreach ($receipt->payments as $payment)
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <span class="text-xs">{{ $payment->payment_date->translatedFormat('d M Y') }} · {{ $payment->method->label() }}</span>
                                <x-money :amount="$payment->amount" class="text-sm" />
                            </li>
                        @endforeach
                    </ul>
                </x-panel>
            @endif
        </div>
    </div>

    @if ($receipt->status->value === 'draft' && auth()->user()->hasAbility('feed.receive'))
        <x-modal id="cancel-receipt-modal" :title="__('Cancel receipt :number?', ['number' => $receipt->receipt_number])"
                 :description="__('Only a confirmed receipt adds stock. Cancelling leaves inventory untouched and keeps the record as evidence.')">
            <form method="POST" action="{{ route('goods-receipts.cancel', $receipt) }}" class="mt-4">
                @csrf
                <x-form.actions bare>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Keep receipt') }}</button>
                    <x-form.submit variant="error">{{ __('Cancel receipt') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection