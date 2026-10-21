@extends('layouts.document', [
    'title' => __('Purchase order').' '.$purchaseOrder->po_number,
    'back' => route('purchase-orders.show', $purchaseOrder),
])

@php
    $money = fn (float|int|string|null $amount): string => 'Rp'.\App\Support\Number::format($amount, 0);
    $partyLines = collect([
        $purchaseOrder->supplier->address,
        $purchaseOrder->supplier->phone ? 'Tel. '.$purchaseOrder->supplier->phone : null,
        $purchaseOrder->supplier->email,
        $purchaseOrder->supplier->tax_number ? __('Tax number').': '.$purchaseOrder->supplier->tax_number : null,
    ])->filter()->values()->all();

    $meta = [
        __('Order date') => $purchaseOrder->order_date?->translatedFormat('d M Y') ?? '—',
        __('Expected arrival') => $purchaseOrder->expected_date?->translatedFormat('d M Y') ?? '—',
        __('Terms') => $purchaseOrder->supplier->payment_terms_days.' '.__('days'),
    ];
@endphp

@section('content')
    <x-official-document
        :title="__('Purchase order')"
        :number="$purchaseOrder->po_number"
        :date="$purchaseOrder->order_date?->translatedFormat('d M Y') ?? '—'"
        :status="$purchaseOrder->status->label()"
        :status-tone="$purchaseOrder->status->badgeClass()"
        :party-label="__('Supplier')"
        :party-name="$purchaseOrder->supplier->name"
        :party-lines="$partyLines"
        :meta="$meta"
        :notes="$purchaseOrder->notes"
        :signatures="[
            ['label' => __('Issued by'), 'name' => $purchaseOrder->creator?->name ?? '—'],
            ['label' => __('Received by'), 'name' => $purchaseOrder->supplier->name],
        ]">
        <x-slot:lines>
            <table class="w-full min-w-[36rem] border-collapse text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-300 text-[0.65rem] uppercase tracking-wider text-slate-500">
                        <th class="py-2 pr-3 font-semibold">{{ __('Feed item') }}</th>
                        <th class="py-2 pr-3 text-right font-semibold">{{ __('Quantity') }}</th>
                        <th class="py-2 pr-3 text-right font-semibold">{{ __('Price') }}</th>
                        <th class="py-2 text-right font-semibold">{{ __('Value') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchaseOrder->items as $item)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-2 pr-3">
                                <p class="font-medium">{{ $item->feedItem->name }}</p>
                                <p class="font-mono text-[0.65rem] text-slate-500">{{ $item->feedItem->code }}</p>
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums">
                                {{ \App\Support\Number::format($item->quantity, 2) }}
                                {{ $item->unit->shortLabel() }}
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums">{{ $money($item->unit_price) }}</td>
                            <td class="py-2 text-right tabular-nums">{{ $money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-slot:lines>

        <x-slot:totals>
            <dl class="space-y-1 text-xs">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Subtotal') }}</dt>
                    <dd class="tabular-nums">{{ $money($purchaseOrder->subtotal) }}</dd>
                </div>
                @if ((float) $purchaseOrder->discount_amount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Discount') }}</dt>
                        <dd class="tabular-nums">&minus;{{ $money($purchaseOrder->discount_amount) }}</dd>
                    </div>
                @endif
                @if ((float) $purchaseOrder->tax_amount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Tax') }}</dt>
                        <dd class="tabular-nums">{{ $money($purchaseOrder->tax_amount) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 border-t border-slate-300 pt-1 text-sm font-semibold">
                    <dt>{{ __('Total') }}</dt>
                    <dd class="tabular-nums">{{ $money($purchaseOrder->total) }}</dd>
                </div>
            </dl>
        </x-slot:totals>
    </x-official-document>
@endsection
