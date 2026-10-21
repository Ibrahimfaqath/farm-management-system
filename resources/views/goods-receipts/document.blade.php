@extends('layouts.document', [
    'title' => __('Goods receipt').' '.$receipt->receipt_number,
    'back' => route('goods-receipts.show', $receipt),
])

@php
    $money = fn (float|int|string|null $amount): string => 'Rp'.\App\Support\Number::format($amount, 0);
    $partyLines = collect([
        $receipt->supplier->address,
        $receipt->supplier->phone ? 'Tel. '.$receipt->supplier->phone : null,
        $receipt->supplier->email,
    ])->filter()->values()->all();

    $meta = [
        __('Received date') => $receipt->received_date->translatedFormat('d M Y'),
        __('Delivery note') => $receipt->delivery_note_reference ?? '—',
        __('Purchase order') => $receipt->purchaseOrder?->po_number ?? __('No purchase order'),
    ];
@endphp

@section('content')
    <x-official-document
        :title="__('Goods receipt')"
        :number="$receipt->receipt_number"
        :date="$receipt->received_date->translatedFormat('d M Y')"
        :status="$receipt->status->label()"
        :status-tone="$receipt->status->badgeClass()"
        :party-label="__('Supplier')"
        :party-name="$receipt->supplier->name"
        :party-lines="$partyLines"
        :meta="$meta"
        :notes="$receipt->quality_notes"
        :signatures="[
            ['label' => __('Received by'), 'name' => $receipt->receiver?->name ?? '—'],
            ['label' => __('Issued by'), 'name' => $receipt->supplier->name],
        ]">
        <x-slot:lines>
            <table class="w-full min-w-[36rem] border-collapse text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-300 text-[0.65rem] uppercase tracking-wider text-slate-500">
                        <th class="py-2 pr-3 font-semibold">{{ __('Feed item') }}</th>
                        <th class="py-2 pr-3 text-right font-semibold">{{ __('Received') }}</th>
                        <th class="py-2 pr-3 text-right font-semibold">{{ __('Price') }}</th>
                        <th class="py-2 pr-3 text-right font-semibold">{{ __('Line total') }}</th>
                        <th class="py-2 text-right font-semibold">{{ __('Batch') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($receipt->items as $item)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-2 pr-3">
                                <p class="font-medium">{{ $item->feedItem->name }}</p>
                                <p class="font-mono text-[0.65rem] text-slate-500">{{ $item->feedItem->code }}</p>
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums">
                                {{ \App\Support\Number::format($item->quantity_received, 2) }}
                                {{ $item->unit->shortLabel() }}
                            </td>
                            <td class="py-2 pr-3 text-right tabular-nums">{{ $money($item->unit_price) }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums">{{ $money($item->line_total) }}</td>
                            <td class="py-2 text-right font-mono text-[0.65rem] text-slate-500">{{ $item->batch?->batch_code ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-slot:lines>

        <x-slot:totals>
            <dl class="space-y-1 text-xs">
                <div class="flex justify-between gap-4 border-t border-slate-300 pt-1 text-sm font-semibold">
                    <dt>{{ __('Total') }}</dt>
                    <dd class="tabular-nums">{{ $money($receipt->total_amount) }}</dd>
                </div>
            </dl>
        </x-slot:totals>
    </x-official-document>
@endsection
