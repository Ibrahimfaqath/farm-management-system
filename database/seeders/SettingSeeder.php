<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['default_payment_terms_days', 30, 'Penjualan', 'integer'],
            ['default_invoice_prefix', 'INV', 'Penjualan', 'string'],
            ['production_decline_threshold_pct', 10, 'Produksi', 'integer'],
            ['production_decline_window_days', 14, 'Produksi', 'integer'],
            ['low_stock_default_percent', 25, 'Inventori', 'integer'],
            ['allow_negative_stock_override', 1, 'Inventori', 'boolean'],
            ['currency', 'IDR', 'Umum', 'string'],
            ['company_name', 'Peternakan Suka Maju', 'Umum', 'string'],
        ];

        foreach ($settings as [$key, $value, $group, $type]) {
            Setting::put($key, $value, $group, $type);
        }

        $this->command?->info('Pengaturan sistem dibuat.');
    }
}
