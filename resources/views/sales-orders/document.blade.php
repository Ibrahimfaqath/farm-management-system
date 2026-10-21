@extends('layouts.document', [
    'title' => __('Sales order').' '.$salesOrder->so_number,
    'back' => route('sales-orders.show', $salesOrder),
])

@php
    $money = fn (float|int|string|null $amount): string => 'Rp'.\App\Support\Number::format($amount, 0);
    $partyLines = collect([
        $salesOrder->customer->address,
        $salesOrder->customer->phone ? 'Tel. '.$salesOrder->customer->phone : null,
        $salesOrder->customer->email,
    ])->filter()->values()->all();

    $meta = [
        __('Order date') => $salesOrder->order_date->translatedFormat('d M Y'),
        __('Terms') => $salesOrder->customer->payment_terms_days.' '.__('days'),
        __('Status') => $salesOrder->status->label(),
    ];
@endphp

@section('content')
    <x-official-document
        :title="__('Sales order')"
        :number="$salesOrder->so_number"
        :date="$salesOrder->order_date->translatedFormat('d M Y')"
        :status="$salesOrder->status->label()"
        :status-tone="$salesOrder->status->badgeClass()"
        :party-label="__('Customer')"
        :party-name="$salesOrder->customer->name"
        :party-lines="$partyLines"
        :meta="$meta"
        :notes="$salesOrder->notes"
        :signatures="[
            ['label' => __('Issued by'), 'name' => $salesOrder->creator?->name ?? '—'],
            ['label' => __('Received by'), 'name' => $salesOrder->customer->name],
        ]">
        <x-slot:lines>
            <table class="w-full min-w-[32rem] border-collapse text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-300 text-[0.65rem] uppercase tracking-wider text-slate-500">
                        <th class="py-2 pr-3 font-semibold">{{ __('Item') }}</th>
                        <th class="py-2 pr-3 text-right font-semibold">{{ __('Quantity') }}</th>
                        <th class="py-2 pr-3 text-right font-semibold">{{ __('Unit price') }}</th>
                        <th class="py-2 text-right font-semibold">{{ __('Line total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salesOrder->items as $item)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-2 pr-3 font-medium">{{ $item->description }}</td>
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
                    <dd class="tabular-nums">{{ $money($salesOrder->subtotal) }}</dd>
                </div>
                @if ((float) $salesOrder->discount_amount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Discount') }}</dt>
                        <dd class="tabular-nums">&minus;{{ $money($salesOrder->discount_amount) }}</dd>
                    </div>
                @endif
                @if ((float) $salesOrder->tax_amount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Tax') }}</dt>
                        <dd class="tabular-nums">{{ $money($salesOrder->tax_amount) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 border-t border-slate-300 pt-1 text-sm font-semibold">
                    <dt>{{ __('Total') }}</dt>
                    <dd class="tabular-nums">{{ $money($salesOrder->total) }}</dd>
                </div>
            </dl>
        </x-slot:totals>
    </x-official-document>
@endsection
