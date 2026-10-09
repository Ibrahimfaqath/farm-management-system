@php
    $groups = collect($fields)->groupBy('group');
@endphp
@extends('layouts.app', ['title' => 'Pengaturan'])

@section('content')
    <x-page-header title="Pengaturan Sistem" subtitle="Konfigurasi yang memengaruhi perilaku aplikasi">
        <x-slot:actions>
            @if (auth()->user()->hasAbility('audit.view') || auth()->user()->hasAbility('settings.manage'))
                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm">Jejak Aktivitas</a>
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
            <div class="rounded-box border border-base-300 bg-base-100 p-4">
                <x-form.actions>
                    <button class="btn btn-primary btn-sm">Simpan Pengaturan</button>
                </x-form.actions>
            </div>
        @else
            <x-alert-row tone="info" title="Mode baca saja"
                         description="Hanya peran owner yang dapat mengubah pengaturan sistem." />
        @endif
    </form>

    @if ($canManage)
        <div class="mt-6 max-w-4xl">
            <x-panel title="Pengguna Sistem" description="Akun yang dapat masuk ke aplikasi">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Peternakan</th><th class="text-right">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td class="font-medium">{{ $user->name }}</td>
                                    <td class="text-base-content/65">{{ $user->email }}</td>
                                    <td><x-badge :label="$user->role->label()" class="badge-ghost" /></td>
                                    <td class="text-base-content/65">{{ $user->farm?->name ?? 'Semua' }}</td>
                                    <td class="text-right">
                                        <button type="button" class="btn btn-ghost btn-xs"
                                                onclick="editUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ $user->role->value }}', '{{ $user->farm_id }}')">
                                            Ubah
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>

            <div class="mt-4 rounded-box border border-base-300 bg-base-100 p-4">
                <button type="button" class="btn btn-primary btn-sm" onclick="editUser(null)">+ Tambah Pengguna</button>
            </div>

            <dialog id="user-modal" class="modal">
                <div class="modal-box max-w-lg">
                    <h3 class="text-base font-semibold" id="user-modal-title">Tambah Pengguna</h3>
                    <form method="POST" action="{{ route('settings.users') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="id" id="user-id">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-form.input name="name" label="Nama" required class="sm:col-span-2" />
                            <x-form.input name="email" label="Email" type="email" required />
                            <x-form.select name="role" label="Peran" :options="$roles" required />
                            <x-form.select name="farm_id" label="Peternakan"
                                           :options="App\Models\Farm::orderBy('name')->pluck('name', 'id')"
                                           placeholder="— Semua peternakan —" />
                            <x-form.input name="phone" label="Telepon" />
                            <x-form.input name="password" label="Kata Sandi" type="password" minlength="8" />
                            <x-form.input name="password_confirmation" label="Ulangi Kata Sandi" type="password" minlength="8" />
                        </div>
                        <div class="modal-action">
                            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('user-modal').close()">Batal</button>
                            <button class="btn btn-primary btn-sm">Simpan Pengguna</button>
                        </div>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
            </dialog>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        function editUser(id, name, email, role, farmId) {
            const modal = document.getElementById('user-modal');
            document.getElementById('user-modal-title').textContent = id ? 'Ubah Pengguna' : 'Tambah Pengguna';
            document.getElementById('user-id').value = id ?? '';
            modal.querySelector('[name="name"]').value = name ?? '';
            modal.querySelector('[name="email"]').value = email ?? '';
            modal.querySelector('[name="role"]').value = role ?? '';
            modal.querySelector('[name="farm_id"]').value = farmId ?? '';
            modal.querySelector('[name="password"]').required = ! id;
            modal.querySelector('[name="password_confirmation"]').required = ! id;
            modal.showModal();
        }
    </script>
@endpush