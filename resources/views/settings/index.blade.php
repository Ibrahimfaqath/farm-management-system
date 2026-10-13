@php
    $groups = collect($fields)->groupBy('group');
@endphp
@extends('layouts.app', ['title' => __('Settings')])

@section('content')
    <x-page-header :title="__('System settings')" :subtitle="__('Configuration that affects how the application behaves.')">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('audit.view') || auth()->user()->hasAbility('settings.manage'))
                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm">{{ __('Activity trail') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('settings.update') }}" class="max-w-4xl space-y-4">
        @csrf

        @foreach ($groups as $group => $groupFields)
            <x-panel :title="$group">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($groupFields as $key => $field)
                        @if ($field['type'] === 'boolean')
                            <div class="sm:col-span-2">
                                <x-form.checkbox :name="'settings['.$key.']'" :label="$field['label']"
                                                 :hint="$field['hint'] ?? null"
                                                 :checked="$values[$key] ?? false" value="1" />
                            </div>
                        @else
                            <x-form.input :name="'settings['.$key.']'" :label="$field['label']"
                                          :type="$field['type'] === 'integer' ? 'number' : 'text'"
                                          :value="$values[$key] ?? ''"
                                          :hint="$field['hint'] ?? null"
                                          :min="$field['type'] === 'integer' ? 0 : null"
                                          :disabled="! $canManage" />
                        @endif
                    @endforeach
                </div>
            </x-panel>
        @endforeach

        @if ($canManage)
            <x-form.actions boxed>
                <x-form.submit>{{ __('Save settings') }}</x-form.submit>
            </x-form.actions>
        @else
            <x-alert-row tone="info" :title="__('Read-only mode')"
                         :description="__('Only the owner role can change system settings.')" />
        @endif
    </form>

    @if ($canManage)
        <div class="mt-6 max-w-4xl">
            <x-panel :title="__('System users')" :description="__('Accounts that can sign in to the application.')" padding="p-0">
                <x-slot:actions>
                    <button type="button" class="btn btn-primary btn-sm" data-user-modal-open>
                        <x-icon name="plus" :size="16" />
                        {{ __('Add user') }}
                    </button>
                </x-slot:actions>

                <div class="table-scroll">
                    <table class="table app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Farm') }}</th>
                                <th class="row-actions">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td class="font-medium">{{ $user->name }}</td>
                                    <td class="text-base-content/65">{{ $user->email }}</td>
                                    <td><x-badge :label="$user->role->label()" class="badge-ghost" /></td>
                                    <td class="text-base-content/65">{{ $user->farm?->name ?? __('All farms') }}</td>
                                    <td class="row-actions">
                                        <x-actions-menu :label="__('Actions for :name', ['name' => $user->name])">
                                            <li>
                                                <button type="button" class="flex w-full items-center gap-2" data-user-modal-open
                                                        data-user="{{ json_encode([
                                                            'id' => $user->id,
                                                            'name' => $user->name,
                                                            'email' => $user->email,
                                                            'role' => $user->role->value,
                                                            'farmId' => $user->farm_id,
                                                        ], JSON_THROW_ON_ERROR) }}">
                                                    <x-icon name="edit" :size="15" class="text-base-content/50" />
                                                    {{ __('Edit') }}
                                                </button>
                                            </li>
                                        </x-actions-menu>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
        </div>

        <x-modal id="user-modal" :title="old('id') ? __('Edit user') : __('Add user')" size="max-w-xl">
            <form method="POST" action="{{ route('settings.users') }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="id" id="user-id" value="{{ old('id') }}">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.input name="name" :label="__('Name')" required class="sm:col-span-2" />
                    <x-form.input name="email" :label="__('Email')" type="email" required />
                    <x-form.select name="role" :label="__('Role')" :options="$roles" required />
                    <x-form.select name="farm_id" :label="__('Farm')"
                                   :options="App\Models\Farm::orderBy('name')->pluck('name', 'id')"
                                   :placeholder="__('— All farms —')" />
                    <x-form.input name="phone" :label="__('Phone')" />
                    <x-form.input name="password" :label="__('Password')" type="password" minlength="8"
                                  :hint="__('Leave empty to keep the current password.')" />
                    <x-form.input name="password_confirmation" :label="__('Confirm password')" type="password" minlength="8" />
                </div>
                <x-form.actions>
                    <button type="button" class="btn btn-ghost btn-sm" data-modal-close>{{ __('Cancel') }}</button>
                    <x-form.submit>{{ __('Save user') }}</x-form.submit>
                </x-form.actions>
            </form>
        </x-modal>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('user-modal');

            if (!modal) {
                return;
            }

            var title = modal.querySelector('.modal-box h3');
            var idField = document.getElementById('user-id');
            var fields = {
                name: modal.querySelector('[name="name"]'),
                email: modal.querySelector('[name="email"]'),
                role: modal.querySelector('[name="role"]'),
                farm_id: modal.querySelector('[name="farm_id"]'),
                phone: modal.querySelector('[name="phone"]'),
                password: modal.querySelector('[name="password"]'),
                password_confirmation: modal.querySelector('[name="password_confirmation"]')
            };

            // Values are read from a JSON data attribute rather than being
            // interpolated into inline JS, so an apostrophe in a name can no
            // longer break the handler.
            var addTitle = title.textContent.trim();
            var editTitle = @json(__('Edit user'));

            document.addEventListener('click', function (event) {
                var trigger = event.target instanceof Element
                    ? event.target.closest('[data-user-modal-open]')
                    : null;

                if (!trigger) {
                    return;
                }

                event.preventDefault();

                var user = trigger.dataset.user ? JSON.parse(trigger.dataset.user) : null;

                title.textContent = user ? editTitle : addTitle;
                idField.value = user ? user.id : '';
                fields.name.value = user ? user.name : '';
                fields.email.value = user ? user.email : '';
                fields.role.value = user ? user.role : '';
                fields.farm_id.value = user && user.farmId ? user.farmId : '';
                fields.phone.value = '';
                fields.password.value = '';
                fields.password_confirmation.value = '';

                var required = !user;

                fields.password.required = required;
                fields.password_confirmation.required = required;

                modal.showModal();
            });
        })();
    </script>
@endpush