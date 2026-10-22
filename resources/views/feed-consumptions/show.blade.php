@extends('layouts.app', ['title' => __('Consumption').' '.$consumption->consumption_number])

@section('content')
    <x-page-header :title="$consumption->consumption_number"
                   :subtitle="($consumption->barn?->name ?? __('No barn')).' · '.$consumption->consumption_date->translatedFormat('d M Y')"
                   :back="route('feed-consumptions.index')"
                   :breadcrumbs="[
                       ['label' => __('Feed consumption'), 'url' => route('feed-consumptions.index')],
                       ['label' => $consumption->consumption_number],
                   ]">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('feed.consume'))
                <button type="button" class="btn btn-outline btn-error btn-sm" data-modal-open="reverse-modal">
                    {{ __('Reverse') }}
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($consumption->allow_negative_stock)
        <div class="mb-4">
            <x-alert-row tone="error"
                         :title="__('This entry used the negative stock override')"
                         :description="__('Reason: :reason', ['reason' => $consumption->override_reason ?? __('not recorded')]).($consumption->overrideBy ? ' '.__('Approved by :name.', ['name' => $consumption->overrideBy->name]) : '')" />
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat :label="__('Total quantity')" :value="\App\Support\Number::format((float) $consumption->total_quantity, 2)" />
        <x-stat :label="__('Consumption value')" :value="'Rp'.number_format((float) $consumption->total_amount, 0, ',', '.')" tone="warning" />
        <x-stat :label="__('Animals')" :value="$consumption->animal_count" />
        <x-stat :label="__('Average per animal')" :value="\App\Support\Number::format((float) $consumption->average_per_animal, 2)"
               :hint="$consumption->barn?->name" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Effect on stock')" padding="p-0"
                     :description="__('Stock before and after this entry, plus the batch that was consumed.')">
                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Feed item') }}</th>
                                <th class="num">{{ __('Used') }}</th>
                                <th>{{ __('Batch') }}</th>
                                <th class="num">{{ __('Stock before') }}</th>
                                <th class="num">{{ __('Stock after') }}</th>
                                <th class="num">{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody class="motion-rows">
                            @foreach ($consumption->items as $item)
                                <tr>
                                    <td class="font-medium">{{ $item->feedItem?->name }}</td>
                                    <td class="num">{{ \App\Support\Number::format((float) $item->quantity, 2) }} <span class="text-meta text-base-content/50">{{ $item->unit->shortLabel() }}</span></td>
                                    <td class="text-meta font-mono">{{ $item->batch?->batch_code ?? __('Automatic FIFO') }}</td>
                                    <td class="num text-base-content/65">{{ \App\Support\Number::format((float) $item->stock_before, 2) }}</td>
                                    <td class="num font-medium {{ (float) $item->stock_after < 0 ? 'text-tone-error' : 'text-tone-success' }}">
                                        {{ \App\Support\Number::format((float) $item->stock_after, 2) }}
                                    </td>
                                    <td class="num"><x-money :amount="$item->line_total" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>

            <x-panel :title="__('Allocation to animals')" padding="p-0"
                     :description="__('Operational detail per animal. This allocation never reduces stock a second time.')">
                @if ($groupedByAnimal->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">
                        {{ __('This entry was recorded at barn level. Per-animal allocation is optional.') }}
                    </p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Animal') }}</th>
                                    <th>{{ __('Item') }}</th>
                                    <th class="num">{{ __('Estimate') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($groupedByAnimal as $animalId => $allocations)
                                    @foreach ($allocations as $allocation)
                                        <tr>
                                            <td>
                                                @if ($allocation->animal)
                                                    <a href="{{ route('animals.show', $allocation->animal) }}" class="link link-hover font-medium text-primary">
                                                        {{ $allocation->animal->animal_code }}
                                                    </a>
                                                @else
                                                    <span class="text-base-content/45">{{ __('Animal #:id', ['id' => $animalId]) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-base-content/70">{{ $allocation->item?->feedItem?->name ?? '—' }}</td>
                                            <td class="num">{{ \App\Support\Number::format((float) $allocation->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>

            <x-panel :title="__('Stock movements')" padding="p-0">
                @if ($movements->isEmpty())
                    <p class="p-4 text-sm text-base-content/55">{{ __('No stock movements yet.') }}</p>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Item') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th class="num">{{ __('Quantity') }}</th>
                                    <th>{{ __('Batch') }}</th>
                                </tr>
                            </thead>
                            <tbody class="motion-rows">
                                @foreach ($movements as $movement)
                                    <tr>
                                        <td>{{ $movement->feedItem?->name }}</td>
                                        <td><x-badge :label="$movement->type->label()" :class="$movement->type->badgeClass()" /></td>
                                        <td class="num {{ $movement->direction()->sign() > 0 ? 'text-tone-success' : 'text-tone-warning' }}">
                                            {{ $movement->direction()->sign() > 0 ? '+' : '−' }}{{ \App\Support\Number::format((float) $movement->quantity, 2) }}
                                        </td>
                                        <td class="text-meta font-mono">{{ $movement->batch?->batch_code ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Information')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Farm') }}</dt>
                    <dd class="text-right">{{ $consumption->farm?->name }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Barn') }}</dt>
                    <dd class="text-right">{{ $consumption->barn?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Recorded by') }}</dt>
                    <dd class="text-right">{{ $consumption->creator?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Date') }}</dt>
                    <dd class="text-right">{{ $consumption->consumption_date->translatedFormat('d M Y') }}</dd>
                </div>
            </dl>
            @if ($consumption->notes)
                <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $consumption->notes }}</p>
            @endif
        </x-panel>
    </div>

    @if (auth()->user()->hasAbility('feed.consume'))
        <x-modal id="reverse-modal" :title="__('Reverse consumption')"
                 :description="__('The original entry stays on file. A reversal creates a new stock-in movement with the recorded reason.')">
            <form method="POST" action="{{ route('feed-consumptions.reverse', $consumption) }}" class="mt-4 space-y-3">
                @csrf
                <x-form.input name="reason" :label="__('Reason for reversal')" required minlength="10"
                              :placeholder="__('For example: duplicate entry by the operator')" />
                <x-form.actions>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Cancel') }}</button>
                    <x-form.submit variant="error">{{ __('Reverse') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection