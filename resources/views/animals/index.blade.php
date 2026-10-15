@extends('layouts.app', ['title' => __('Livestock')])

@section('content')
    <x-page-header :title="__('Livestock')" :subtitle="__('Every animal, its status and where it is kept.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('animal.manage'))
                <a href="{{ route('animals.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" :size="16" />
                    {{ __('Add animal') }}
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
        <x-stat :label="__('Total')" :value="$summary['total']" />
        <x-stat :label="__('Active')" :value="$summary['active']" tone="primary" />
        <x-stat :label="__('Lactating')" :value="$summary['lactating']" />
        <x-stat :label="__('Pregnant')" :value="$summary['pregnant']" />
        <x-stat :label="__('Calf')" :value="$summary['calves']" />
        <x-stat :label="__('Left the herd')"
               :value="$summary['sold'] + $summary['deceased'] + $summary['transferred']"
               :hint="__('Sold, died or moved')" />
    </div>

    <x-panel padding="p-0">
        <x-filter-bar :search-placeholder="__('Search by code or name…')" columns="sm:grid-cols-2 lg:grid-cols-4">
            <x-filter-select name="status" :options="$statusOptions" :selected="$filters['status'] ?? ''"
                            :placeholder="__('All statuses')" :label="__('Filter by status')" />

            <x-filter-select name="production_status"
                :options="$productionOptions"
                :selected="$filters['production_status'] ?? ''"
                :placeholder="__('All production statuses') "
                :label="__('Filter by production status') " />

            <x-filter-select name="barn_id"
                :options="$barns"
                :selected="$filters['barn_id'] ?? ''"
                :placeholder="__('All barns') "
                :label="__('Filter by barn') " />

            <x-filter-select name="breeding_status"
                :options="$breedingOptions"
                :selected="$filters['breeding_status'] ?? ''"
                :placeholder="__('All breeding statuses') "
                :label="__('Filter by breeding status') " />

            <x-filter-select name="sex"
                :options="$sexOptions"
                :selected="$filters['sex'] ?? ''"
                :placeholder="__('All sexes') "
                :label="__('Filter by sex') " />

            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="health_issue" value="1" @checked($filters['health_issue'] ?? false)
                       class="checkbox checkbox-sm checkbox-primary">
                {{ __('Has health issues') }}
            </label>
        </x-filter-bar>

        @if ($animals->isEmpty())
            <div class="p-4">
                <x-empty-state :label="__('No animals registered')"
                              :description="__('Register the first animal to start recording production, health and breeding.')"
                              :url="route('animals.create')"
                              :submit-label="__('Add animal')" />
            </div>
        @else
            {{--
                Below `lg` the nine columns are replaced by a list of cards.
                Ten columns across a 375px screen meant "Holstein Friesian" wrapped
                over two lines, rows ran to about 100px each, and the barn and
                status the reader came for sat off-screen with no hint that they
                existed. The card shows the animal, its state, its breed, sex and
                age, and where it is — the whole row, not a summary of it.

                The table stays in the document for `lg` and above, so the desktop
                layout is untouched and nothing is hidden at either size.
            --}}
            <x-record-cards :label="__('Livestock list')">
                @foreach ($animals as $animal)
                    <x-record-card :href="route('animals.show', $animal)"
                                   :title="$animal->animal_code.' '.__('Details')">
                        {{-- Identity, then the facts that describe it, then
                             where it is and what state it is in. Three lines,
                             so a phone shows four or five animals at a time
                             instead of one and a half. --}}
                        <p class="truncate font-medium leading-tight">
                            <span class="text-primary">{{ $animal->animal_code }}</span>
                            @if ($animal->name)
                                <span class="text-base-content">· {{ $animal->name }}</span>
                            @endif
                        </p>

                        <p class="text-meta mt-0.5 truncate text-base-content/55">
                            {{ $animal->breed?->name ?? '—' }} ·
                            {{ $animal->sex->shortLabel() }} ·
                            {{ $animal->age_label }}
                        </p>

                        <p class="mt-1.5 flex flex-wrap items-center gap-1.5 text-meta">
                            <x-badge :label="$animal->status->label()" :class="$animal->status->badgeClass()" />
                            @if ($animal->open_health_records_count > 0)
                                <x-badge :label="__('In care')" class="badge-soft badge-error" />
                            @endif
                            <span class="min-w-0 truncate text-base-content/55">
                                {{ $animal->barn?->name ?? '—' }}
                            </span>
                        </p>

                        <x-slot:actions>
                            <x-actions-menu :label="__('Actions for :code', ['code' => $animal->animal_code])">
                                <li>
                                    <a href="{{ route('animals.show', $animal) }}">
                                        <x-icon name="eye" :size="15" />
                                        {{ __('Details') }}
                                    </a>
                                </li>
                                @if ($animal->status->isOpen() && auth()->user()->hasAbility('animal.manage'))
                                    <li>
                                        <a href="{{ route('animals.edit', $animal) }}">
                                            <x-icon name="edit" :size="15" />
                                            {{ __('Edit animal') }}
                                        </a>
                                    </li>
                                @endif
                            </x-actions-menu>
                        </x-slot:actions>
                    </x-record-card>
                @endforeach
            </x-record-cards>

            <div class="table-scroll hidden lg:block">
                <table class="table app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Breed') }}</th>
                            <th>{{ __('Sex') }}</th>
                            <th>{{ __('Age') }}</th>
                            <th>{{ __('Barn') }}</th>
                            <th>{{ __('Production') }}</th>
                            <th>{{ __('Breeding') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="row-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="motion-rows">
                        @foreach ($animals as $animal)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('animals.show', $animal) }}" class="link link-hover font-medium text-primary">
                                        {{ $animal->animal_code }}
                                    </a>
                                </td>
                                <td>{{ $animal->name ?: '—' }}</td>
                                <td class="text-base-content/75">{{ $animal->breed?->name ?? '—' }}</td>
                                <td>{{ $animal->sex->shortLabel() }}</td>
                                <td class="whitespace-nowrap text-base-content/70">{{ $animal->age_label }}</td>
                                {{-- Capped at 8rem rather than 10. Ten columns share the panel, and at a
                                     1280px viewport the whole table came to 999px
                                     against 982px of room, so the last 17px — and
                                     with it the row's action button — sat in the
                                     scroll container rather than on the page.
                                     "Kandang Laktasi B" fits comfortably. --}}
                                <td class="max-w-[8rem] truncate text-base-content/70"
                                    title="{{ $animal->barn?->name ?? '—' }}">{{ $animal->barn?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap"><x-badge :label="$animal->production_status->label()" :class="$animal->production_status->badgeClass()" /></td>
                                <td class="whitespace-nowrap"><x-badge :label="$animal->breeding_status->label()" :class="$animal->breeding_status->badgeClass()" /></td>
                                <td class="whitespace-nowrap">
                                    <div class="flex flex-wrap items-center gap-1">
                                        <x-badge :label="$animal->status->label()" :class="$animal->status->badgeClass()" />
                                        @if ($animal->open_health_records_count > 0)
                                            <x-badge :label="__('In care')" class="badge-soft badge-error" />
                                        @endif
                                    </div>
                                </td>
                                <td class="row-actions">
                                    <x-actions-menu :label="__('Actions for :code', ['code' => $animal->animal_code])">
                                        <li>
                                            <a href="{{ route('animals.show', $animal) }}">
                                                <x-icon name="eye" :size="15" />
                                                {{ __('Details') }}
                                            </a>
                                        </li>
                                        @if ($animal->status->isOpen() && auth()->user()->hasAbility('animal.manage'))
                                            <li>
                                                <a href="{{ route('animals.edit', $animal) }}">
                                                    <x-icon name="edit" :size="15" />
                                                    {{ __('Edit animal') }}
                                                </a>
                                            </li>
                                        @endif
                                    </x-actions-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$animals" />
        @endif
    </x-panel>
@endsection