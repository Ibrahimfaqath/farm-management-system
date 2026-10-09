<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Editable system settings, keyed by name. Values are stored as strings and
     * cast according to the declared type.
     *
     * @var array<string, array{label: string, group: string, type: string, hint?: string, options?: array<string, string>}>
     */
    private const FIELDS = [
        'default_payment_terms_days' => [
            'label' => 'Termin Pembayaran Default',
            'group' => 'Penjualan',
            'type' => 'integer',
            'hint' => 'Dipakai saat membuat faktur baru bila pelanggan tidak punya termin sendiri.',
        ],
        'default_invoice_prefix' => [
            'label' => 'Prefiks Faktur',
            'group' => 'Penjualan',
            'type' => 'string',
            'hint' => 'Contoh: INV',
        ],
        'production_decline_threshold_pct' => [
            'label' => 'Ambang Penurunan Produksi (%)',
            'group' => 'Produksi',
            'type' => 'integer',
            'hint' => 'Hewan ditandai perlu diperiksa bila produksinya turun melebihi nilai ini.',
        ],
        'production_decline_window_days' => [
            'label' => 'Jendela Perbandingan Produksi (hari)',
            'group' => 'Produksi',
            'type' => 'integer',
            'hint' => 'Panjang periode pembanding saat menghitung penurunan produksi.',
        ],
        'low_stock_default_percent' => [
            'label' => 'Ambang Stok Menipis (%)',
            'group' => 'Inventori',
            'type' => 'integer',
            'hint' => 'Cadangan bila item pakan belum memiliki batas pemesanan ulang sendiri.',
        ],
        'allow_negative_stock_override' => [
            'label' => 'Izinkan Override Stok Negatif',
            'group' => 'Inventori',
            'type' => 'boolean',
            'hint' => 'Bila nonaktif, pemakaian melebihi stok selalu ditolak.',
        ],
        'currency' => [
            'label' => 'Mata Uang',
            'group' => 'Umum',
            'type' => 'string',
        ],
        'company_name' => [
            'label' => 'Nama Perusahaan',
            'group' => 'Umum',
            'type' => 'string',
        ],
    ];

    public function index(): View
    {
        $users = User::query()->orderBy('name')->get();

        return view('settings.index', [
            'fields' => self::FIELDS,
            'values' => collect(array_keys(self::FIELDS))->mapWithKeys(fn ($key) => [$key => Setting::get($key)]),
            'users' => $users,
            'roles' => $this->enumOptions(Role::class),
            'canManage' => auth()->user()->hasAbility('settings.manage'),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAbility('settings.manage'), 403);

        $validated = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable'],
        ]);

        foreach ((array) ($validated['settings'] ?? []) as $key => $value) {
            if (! isset(self::FIELDS[$key])) {
                continue;
            }

            Setting::put($key, $value, self::FIELDS[$key]['group'], self::FIELDS[$key]['type']);
        }

        $audit->log('settings.updated', null, 'Pengaturan sistem diperbarui', null, ['keys' => array_keys((array) ($validated['settings'] ?? []))]);

        return redirect()->route('settings.index')->with('status', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Create or update a user account.
     */
    public function saveUser(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAbility('settings.manage'), 403);

        $userId = $request->integer('id') ?: null;

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::enum(Role::class)],
            'farm_id' => ['nullable', 'exists:farms,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi wajib diisi untuk pengguna baru.',
        ]);

        $user = $userId ? User::query()->findOrFail($userId) : new User;
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'farm_id' => $data['farm_id'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $audit->log(
            $userId ? 'settings.user_updated' : 'settings.user_created',
            $user,
            ($userId ? 'Pengguna ' : 'Pengguna baru ').$user->name.' ('.$user->role->label().')',
            $user->email,
        );

        return redirect()->route('settings.index')->with('status', 'Data pengguna tersimpan.');
    }
}
