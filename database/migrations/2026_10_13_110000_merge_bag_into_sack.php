<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `Bag` and `Sack` were the same unit twice.
 *
 * Both converted to 50 kilograms, both carried the label "Sack", and both meant
 * a 50 kg sack — but a record could be filed under either, so the same item
 * could carry two units depending on who entered it. `Bag` is folded into
 * `Sack`, which is the name the label already used.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['feed_items', 'feed_consumption_items', 'products'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (['unit', 'purchase_order_items', 'sales_order_items'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::table($table)->where($column, 'bag')->update([$column => 'sack']);
                }
            }
        }
    }

    public function down(): void
    {
        // The two units were interchangeable, so restoring `bag` for every
        // `sack` is as faithful a rollback as this allows.
        foreach (['feed_items', 'feed_consumption_items', 'products'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (['unit', 'purchase_order_items', 'sales_order_items'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::table($table)->where($column, 'sack')->update([$column => 'bag']);
                }
            }
        }
    }
};
