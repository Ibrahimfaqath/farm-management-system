@extends('layouts.app', ['title' => __('Breeding record').' '.$record->animal->animal_code])

@section('content')
    <x-page-header :title="__('Breeding · :code', ['code' => $record->animal->animal_code])"
                   :subtitle="$record->record_type->label().' · '.$record->breeding_date->translatedFormat('d M Y')"
                   :back="route('breeding-records.index')"
                   :breadcrumbs="[
                       ['label' => __('Breeding records'), 'url' => route('breeding-records.index')],
                       ['label' => $record->animal->animal_code],
                   ]">
        <x-slot:actions>
            @if ($record->pregnancyChecks->isEmpty() && auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('pregnancy-checks.create', ['breeding_record_id' => $record->id]) }}" class="btn btn-primary btn-sm">
                    {{ __('Record check') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-panel :title="__('Breeding details')">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-base-content/55">{{ __('Female') }}</dt>
                        <dd>
                            <a href="{{ route('animals.show', $record->animal) }}" class="link link-hover font-medium text-primary">
                                {{ $record->animal->animal_code }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Sire') }}</dt>
                        <dd>{{ $record->sireLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Date') }}</dt>
                        <dd>{{ $record->breeding_date->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Inseminator') }}</dt>
                        <dd>{{ $record->inseminator ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Semen batch') }}</dt>
                        <dd>{{ $record->semen_batch ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/55">{{ __('Expected next heat') }}</dt>
                        <dd>{{ $record->expected_next_heat_date?->translatedFormat('d M Y') ?? '—' }}</dd>
                    </div>
                </dl>
                @if ($record->heat_signs)
                    <p class="mt-3 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $record->heat_signs }}</p>
                @endif
                @if ($record->notes)
                    <p class="mt-2 rounded-field bg-base-200 p-2.5 text-xs leading-relaxed text-base-content/70">{{ $record->notes }}</p>
                @endif
            </x-panel>

            <x-panel :title="__('Pregnancy check history')" padding="p-0">
                @if ($record->pregnancyChecks->isEmpty())
                    <div class="p-4">
                        <x-empty-state :label="__('No pregnancy checks yet')"
                                      :description="__('Breeding alone is not enough to call an animal pregnant. Record the check result.')"
                                      :url="route('pregnancy-checks.create', ['breeding_record_id' => $record->id])"
                                      :submit-label="__('Record check')" />
                    </div>
                @else
                    <div class="table-scroll">
                        <table class="table app-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Result') }}</th>
                                    <th>{{ __('Expected birth') }}</th>
                                    <th>{{ __('Veterinarian / officer') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($record->pregnancyChecks as $check)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ $check->check_date->translatedFormat('d M Y') }}</td>
                                        <td>{{ $check->method->label() }}</td>
                                        <td><x-badge :label="$check->result->label()" :class="$check->result->badgeClass()" /></td>
                                        <td class="whitespace-nowrap">{{ $check->expectedDueDate()?->translatedFormat('d M Y') ?? '—' }}</td>
                                        <td class="text-base-content/65">{{ $check->veterinarian?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        </div>

        <x-panel :title="__('Animal status')"
                 :description="__('Breeding status follows the check result, never an assumption from breeding.')">
            <dl class="space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Breeding') }}</dt>
                    <dd><x-badge :label="$record->animal->breeding_status->label()" :class="$record->animal->breeding_status->badgeClass()" /></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-base-content/55">{{ __('Production') }}</dt>
                    <dd><x-badge :label="$record->animal->production_status->label()" :class="$record->animal->production_status->badgeClass()" /></dd>
                </div>
            </dl>
        </x-panel>
    </div>
@endsection