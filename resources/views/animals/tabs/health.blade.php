<div class="space-y-4">
    <x-panel :title="__('Health records')" padding="p-0">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('health.manage'))
                <a href="{{ route('health-records.create', ['animal_id' => $animal->id]) }}" class="btn btn-primary btn-xs">
                    <x-icon name="plus" :size="14" />
                    {{ __('Add') }}
                </a>
            @endif
        </x-slot:actions>

        @if ($animal->healthRecords->isEmpty())
            <p class="p-4 text-sm text-base-content/55">{{ __('No health records for :code yet.', ['code' => $animal->animal_code]) }}</p>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Symptoms') }}</th>
                            <th>{{ __('Diagnosis') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Cost') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($animal->healthRecords as $record)
                            <tr>
                                <td class="whitespace-nowrap">{{ $record->record_date->translatedFormat('d M Y') }}</td>
                                <td>{{ $record->record_type->label() }}</td>
                                <td class="max-w-xs truncate">{{ $record->symptoms ?? '—' }}</td>
                                <td class="max-w-xs truncate">{{ $record->diagnosis ?? '—' }}</td>
                                <td><x-badge :label="$record->status->label()" :class="$record->status->badgeClass()" /></td>
                                <td class="num"><x-money :amount="$record->cost ?? 0" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    <x-panel :title="__('Vaccinations')" padding="p-0">
        @if ($animal->vaccinations->isEmpty())
            <p class="p-4 text-sm text-base-content/55">{{ __('No vaccination records yet.') }}</p>
        @else
            <div class="table-scroll">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Vaccine') }}</th>
                            <th>{{ __('Batch') }}</th>
                            <th>{{ __('Administered') }}</th>
                            <th>{{ __('Next due') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($animal->vaccinations as $vaccination)
                            <tr>
                                <td>{{ $vaccination->vaccine_name }}</td>
                                <td class="text-base-content/65">{{ $vaccination->batch_number ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $vaccination->administered_date->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap">
                                    {{ $vaccination->next_due_date?->translatedFormat('d M Y') ?? '—' }}
                                    @if ($vaccination->next_due_date && $vaccination->next_due_date->isPast())
                                        <x-badge :label="__('Due')" class="badge-soft badge-warning" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    <x-panel :title="__('Treatments')" padding="p-0">
        @if ($animal->treatments->isEmpty())
            <p class="p-4 text-sm text-base-content/55">{{ __('No treatments yet.') }}</p>
        @else
            <ul class="divide-y divide-base-300">
                @foreach ($animal->treatments as $treatment)
                    <li class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $treatment->medication_name }}</p>
                            <p class="truncate text-xs text-base-content/55">
                                {{ $treatment->treatment_date->translatedFormat('d M Y') }}
                                · {{ $treatment->dosage ?? __('no dosage recorded') }}
                                · {{ $treatment->veterinarian?->name ?? __('No veterinarian') }}
                            </p>
                        </div>
                        <x-money :amount="$treatment->cost ?? 0" class="shrink-0 text-sm" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-panel>
</div>