<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    /**
     * Domain tables emptied before a demo run.
     *
     * `db:seed` inserts, it does not reset, so a database that was ever
     * populated another way keeps those rows and they sit next to the demo data
     * forever. That is how a farm named "Towne-Beer Farm" with the English
     * village "et" ended up beside "Peternakan Suka Maju", and a duplicate
     * "Limosin" breed filed under "UK" sat next to the real one. Emptying the
     * domain tables first makes `db:seed` mean the same thing every time.
     *
     * @var list<string>
     */
    private const DOMAIN_TABLES = [
        'activity_logs', 'animals', 'barns', 'birth_records', 'breeding_records',
        'breeds', 'cash_transactions', 'customers', 'expenses', 'farms',
        'feed_consumption_allocations', 'feed_consumption_items', 'feed_consumptions',
        'feed_items', 'goods_receipt_items', 'goods_receipts', 'health_records',
        'incomes', 'inventory_batches', 'invoices', 'milk_productions', 'payments',
        'pregnancy_checks', 'products', 'purchase_order_items', 'purchase_orders',
        'sales_order_items', 'sales_orders', 'stock_movements', 'supplier_payments',
        'suppliers', 'treatments', 'users', 'vaccinations',
    ];

    public function run(): void
    {
        $this->resetDomainTables();

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

    /**
     * Empties the domain tables so a demo run is reproducible.
     *
     * Refuses to touch a production database, and checks that every table it is
     * about to empty actually exists so a renamed migration cannot silently
     * leave half the old rows behind.
     */
    private function resetDomainTables(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Production detected: leaving existing data in place.');

            return;
        }

        $tables = array_values(array_filter(
            self::DOMAIN_TABLES,
            fn (string $table) => Schema::hasTable($table),
        ));

        $missing = array_values(array_diff(self::DOMAIN_TABLES, $tables));

        if ($missing !== []) {
            $this->command?->warn('Tables not found, left alone: '.implode(', ', $missing));
        }

        if ($tables === []) {
            return;
        }

        // SQLite ignores `foreign_keys = OFF` inside a transaction, which is
        // exactly where a test suite runs the seeder. The deferred variant is
        // honoured mid-transaction, so children are checked at commit instead
        // of on every statement. Other drivers get the connection-wide switch.
        $deferred = DB::connection()->getDriverName() === 'sqlite';

        if ($deferred) {
            DB::statement('PRAGMA defer_foreign_keys = ON');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        try {
            foreach ($tables as $table) {
                DB::table($table)->delete();
            }
        } finally {
            if ($deferred) {
                DB::statement('PRAGMA defer_foreign_keys = OFF');
            } else {
                Schema::enableForeignKeyConstraints();
            }
        }
    }
}
