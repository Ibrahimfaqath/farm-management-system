<div class="space-y-4">
    <x-panel title="Riwayat Peminahan" padding="p-0">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('breeding.manage'))
                <a href="{{ route('breeding-records.create', ['animal_id' => $animal->id]) }}" class="btn btn-primary btn-xs">+ Catat</a>
            @endif
        </x-slot:actions>
        @if ($animal->breedingRecords->isEmpty())
            <p class="p-4 text-sm text-base-content/55">Belum ada riwayat peminahan.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Pejantan</th><th>Inseminator</th><th>Pemeriksaan</th><th>Perkiraan Lahir</th></tr></thead>
                    <tbody>
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
                                        <span class="text-base-content/45">Belum diperiksa</span>
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

    <x-panel title="Pemeriksaan Kehamilan" padding="p-0">
        @if ($animal->pregnancyChecks->isEmpty())
            <p class="p-4 text-sm text-base-content/55">Belum ada pemeriksaan kehamilan.</p>
        @else
            <ul class="divide-y divide-base-300">
                @foreach ($animal->pregnancyChecks as $check)
                    <li class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <div>
                            <p class="text-sm">{{ $check->check_date->translatedFormat('d M Y') }} · {{ $check->method->label() }}</p>
                            <p class="text-xs text-base-content/55">
                                {{ $check->result->label() }}
                                @if ($check->expected_due_date) · perkiraan lahir {{ $check->expected_due_date->translatedFormat('d M Y') }} @endif
                            </p>
                        </div>
                        <x-badge :label="$check->result->label()" :class="$check->result->badgeClass()" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-panel>

    @if ($birthRecord)
        <x-panel title="Kelahiran Terakhir">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-base-content/55">Tanggal</dt><dd>{{ $birthRecord->birth_date->translatedFormat('d M Y') }}</dd></div>
                <div><dt class="text-base-content/55">Jenis persalinan</dt><dd>{{ $birthRecord->birth_type->label() }}</dd></div>
                <div><dt class="text-base-content/55">Berat lahir</dt><dd>{{ $birthRecord->birth_weight_kg ? $birthRecord->birth_weight_kg.' kg' : '—' }}</dd></div>
                <div><dt class="text-base-content/55">Anak</dt>
                    <dd>
                        @if ($birthRecord->animal)
                            <a class="text-primary hover:underline" href="{{ route('animals.show', $birthRecord->animal) }}">{{ $birthRecord->animal->animal_code }}</a>
                        @else
                            <span class="text-base-content/45">Tidak ada anak hidup</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-panel>
    @endif
</div>
