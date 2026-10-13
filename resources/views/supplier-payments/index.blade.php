@extends('layouts.app', ['title' => __('Supplier payments')])

@section('content')
    <x-page-header :title="__('Supplier payments')"
                   :subtitle="__('Payables arise from confirmed goods receipts and fall as they are paid.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('finance.manage'))
                <a href="{{ route('supplier-payments.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Pay supplier') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <x-stat :label="__('Total payable')" :value="'Rp'.number_format($totalOutstanding, 0, ',', '.')" tone="warning" />
        <x-stat :label="__('Suppliers owing')" :value="count($outstanding)" />
        <x-stat :label="__('All payments')" :value="$payments->total()" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            <x-panel :title="__('Payment history')" padding="p-0">
                @if ($payments->isEmpty())
                    <div class="p-4">
                        <x-empty-state :label="__('No supplier payments yet')"
                                      :description="__('A payment reduces the payable created by a confirmed goods receipt.')"
                                      :url="route('supplier-payments.create')" :submit-label="__('Pay supplier')" />
                    </div>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Supplier') }}</th>
                                    <th>{{ __('Goods receipt') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th class="num">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td class="text-meta font-mono">{{ $payment->payment_number }}</td>
                                        <td class="whitespace-nowrap">{{ $payment->payment_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $payment->supplier->name }}</td>
                                        <td class="text-meta ">
                                            @if ($payment->goodsReceipt)
                                                <a href="{{ route('goods-receipts.show', $payment->goodsReceipt) }}"
                                                   class="link link-hover font-mono text-primary">
                                                    {{ $payment->goodsReceipt->receipt_number }}
                                                </a>
                                            @else
                                                <span class="text-base-content/45">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $payment->method->label() }}</td>
                                        <td class="num font-medium text-tone-warning"><x-money :amount="$payment->amount" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-base-300 p-3">{{ $payments->links() }}</div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Payable by supplier')" :description="__('From confirmed goods receipts')">
            @if (count($outstanding) === 0)
                <p class="text-sm text-base-content/55">{{ __('No outstanding payables.') }}</p>
            @else
                <ul class="space-y-2">
                    @foreach ($outstanding as $row)
                        <li class="flex items-center justify-between gap-3 rounded-field border border-base-300 px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $row['supplier']->name }}</p>
                                <a href="{{ route('suppliers.show', $row['supplier']) }}" class="text-meta link link-hover text-primary">
                                    {{ __('View supplier') }}
                                </a>
                            </div>
                            <x-money :amount="$row['outstanding']" class="shrink-0 text-sm font-medium text-tone-warning" />
                        </li>
                    @endforeach
                </ul>
                @if (auth()->user()->hasAbility('finance.manage'))
                    <a href="{{ route('supplier-payments.create') }}" class="btn btn-primary btn-sm mt-3 w-full">
                        {{ __('Pay supplier') }}
                    </a>
                @endif
            @endif
        </x-panel>
    </div>
@endsection