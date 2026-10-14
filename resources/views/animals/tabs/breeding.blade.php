<div class="space-y-4">
    <x-panel :title="__('Breeding history')" padding="p-0">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('breeding-records.create', ['animal_id' => $animal->id]) }}" class="btn btn-primary btn-xs">
                    <x-icon name="plus" :size="14" />
                    {{ __('Add') }}
                </a>
            @endif
        </x-slot:actions>

        @if ($animal->breedingRecords->isEmpty())
            <p class="p-4 text-sm text-base-content/55">{{ __('No breeding history yet.') }}</p>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Sire / semen') }}</th>
                            <th>{{ __('Inseminator') }}</th>
                            <th>{{ __('Check') }}</th>
                            <th>{{ __('Expected birth') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($animal->breedingRecords as $record)
                            @php $check = $record->pregnancyChecks->last(); @endphp
                            <tr>
                                <td class="whitespace-nowrap">{{ $record->breeding_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $record->record_type->label() }}</td>
                                <td>{{ $record->bull?->animal_code ?? $record->semen_code ?? '—' }}</td>
                                <td class="text-base-content/65">{{ $record->inseminator_name ?? '—' }}</td>
                                <td>
                                    @if ($check)
                                        <x-badge :label="$check->result->label()" :class="$check->result->badgeClass()" />
                                    @else
                                        <span class="text-base-content/45">{{ __('Not checked yet') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">{{ $check?->expected_due_date?->translatedFormat('d M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    <x-panel :title="__('Pregnancy checks')" padding="p-0">
        @if ($animal->pregnancyChecks->isEmpty())
            <p class="p-4 text-sm text-base-content/55">{{ __('No pregnancy checks yet.') }}</p>
        @else
            <ul class="divide-y divide-base-300">
                @foreach ($animal->pregnancyChecks as $check)
                    <li class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm">{{ $check->check_date->translatedFormat('d M Y') }} · {{ $check->method->label() }}</p>
                            <p class="truncate text-xs text-base-content/55">
                                {{ $check->result->label() }}
                                @if ($check->expected_due_date)
                                    · {{ __('expected birth :date', ['date' => $check->expected_due_date->translatedFormat('d M Y')]) }}
                                @endif
                            </p>
                        </div>
                        <x-badge :label="$check->result->label()" :class="$check->result->badgeClass()" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-panel>

    @if ($birthRecord)
        <x-panel :title="__('Most recent birth')">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-base-content/55">{{ __('Date') }}</dt>
                    <dd>{{ $birthRecord->birth_date->translatedFormat('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/55">{{ __('Birth type') }}</dt>
                    <dd>{{ $birthRecord->birth_type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/55">{{ __('Birth weight') }}</dt>
                    <dd>{{ $birthRecord->birth_weight_kg ? $birthRecord->birth_weight_kg.' kg' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/55">{{ __('Calf') }}</dt>
                    <dd>
                        @if ($birthRecord->animal)
                            <a href="{{ route('animals.show', $birthRecord->animal) }}" class="link link-hover text-primary">
                                {{ $birthRecord->animal->animal_code }}
                            </a>
                        @else
                            <span class="text-base-content/45">{{ __('No live calf') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-panel>
    @endif
</div>