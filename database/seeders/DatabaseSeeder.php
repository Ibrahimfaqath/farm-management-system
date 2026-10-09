<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Demo dataset for FARM — Farm Management System.
 *
 * The seeders run in dependency order and use the real application services so
 * the demo data is produced by the same code paths as production input:
 * goods receipts post through InventoryService, consumptions deduct stock FIFO,
 * invoices and payments go through FinanceService, and breeding events update
 * animal status through BreedingService.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            FarmDataSeeder::class,
            InventoryDataSeeder::class,
            HerdHistorySeeder::class,
            MilkProductionSeeder::class,
            SalesFinanceSeeder::class,
        ]);

        $this->command?->newLine();
        $this->command?->info('Data demonstrasi FARM siap digunakan.');
        $this->command?->table(
            ['Peran', 'Email', 'Kata sandi'],
            [
                ['Owner', 'owner@farm.test', 'password'],
                ['Farm Manager', 'manager@farm.test', 'password'],
                ['Farm Worker', 'worker@farm.test', 'password'],
                ['Veterinarian', 'vet@farm.test', 'password'],
                ['Finance / Admin', 'finance@farm.test', 'password'],
            ]
        );
    }
}
