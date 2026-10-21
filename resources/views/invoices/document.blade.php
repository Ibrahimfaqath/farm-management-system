@extends('layouts.document', [
    'title' => __('Invoice').' '.$invoice->invoice_number,
    'back' => route('invoices.show', $invoice),
])

@php
    $items = $invoice->salesOrder?->items ?? collect();
    $paid = (float) $invoice->paid_amount;
    $balance = $invoice->balance();
    $money = fn (float|int|string|null $amount): string => 'Rp'.\App\Support\Number::format($amount, 0);
    $partyLines = collect([
        $invoice->customer->address,
        $invoice->customer->phone ? 'Tel. '.$invoice->customer->phone : null,
        $invoice->customer->email,
    ])->filter()->values()->all();

    $meta = [
        __('Invoice date') => $invoice->invoice_date->translatedFormat('d M Y'),
        __('Due date') => $invoice->due_date->translatedFormat('d M Y'),
        __('Terms') => $invoice->payment_terms_days.' '.__('days'),
    ];

    if ($invoice->salesOrder) {
        $meta[__('Sales order')] = $invoice->salesOrder->so_number;
    }
@endphp

@section('content')
    <x-official-document
        :title="__('Invoice')"
        :number="$invoice->invoice_number"
        :date="$invoice->invoice_date->translatedFormat('d M Y')"
        :status="$invoice->status->label()"
        :status-tone="$invoice->status->badgeClass()"
        :party-label="__('Bill to')"
        :party-name="$invoice->customer->name"
        :party-lines="$partyLines"
        :meta="$meta"
        :notes="$invoice->notes"
        :signatures="[
            ['label' => __('Issued by'), 'name' => $invoice->creator?->name ?? '—'],
            ['label' => __('Received by'), 'name' => $invoice->customer->name],
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
                    @forelse ($items as $item)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-2 pr-3 font-medium">{{ $item->description }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums">
                                {{ \App\Support\Number::format($item->quantity, 2) }}
                                {{ $item->unit->shortLabel() }}
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums">{{ $money($item->unit_price) }}</td>
                            <td class="py-2 text-right tabular-nums">{{ $money($item->line_total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-slate-500">{{ __('This invoice was raised directly, without a sales order.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-slot:lines>

        <x-slot:totals>
            <dl class="space-y-1 text-xs">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Subtotal') }}</dt>
                    <dd class="tabular-nums">{{ $money($invoice->subtotal) }}</dd>
                </div>
                @if ((float) $invoice->discount_amount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Discount') }}</dt>
                        <dd class="tabular-nums">&minus;{{ $money($invoice->discount_amount) }}</dd>
                    </div>
                @endif
                @if ((float) $invoice->tax_amount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Tax') }}</dt>
                        <dd class="tabular-nums">{{ $money($invoice->tax_amount) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 border-t border-slate-300 pt-1 text-sm font-semibold">
                    <dt>{{ __('Total') }}</dt>
                    <dd class="tabular-nums">{{ $money($invoice->total) }}</dd>
                </div>
                <div class="flex justify-between gap-4 text-slate-600">
                    <dt>{{ __('Amount paid') }}</dt>
                    <dd class="tabular-nums">{{ $money($paid) }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-slate-300 pt-1 font-semibold">
                    <dt>{{ __('Outstanding balance') }}</dt>
                    <dd class="tabular-nums">{{ $money($balance) }}</dd>
                </div>
            </dl>
        </x-slot:totals>
    </x-official-document>
@endsection
