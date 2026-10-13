<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fold the old feed categories into the single-axis set.
 *
 * silage    -> forage      it is a forage, not a separate class
 * byproduct -> concentrate rice bran and corn germ are concentrates; their
 *                        origin is not what a ration is planned around
 * mineral   -> supplement
 * vitamin   -> supplement
 *
 * The old values could no longer be parsed into the enum, so any model that
 * cast the column would throw before this ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'silage' => 'forage',
            'roughage' => 'forage',
            'byproduct' => 'concentrate',
            'concentrate' => 'concentrate',
            'mineral' => 'supplement',
            'vitamin' => 'supplement',
            'other' => 'other',
        ];

        foreach ($map as $from => $to) {
            DB::table('feed_items')->where('category', $from)->update(['category' => $to]);
        }

        // Anything predating the enum lands in `other` rather than keeping a
        // value the enum can no longer read.
        DB::table('feed_items')
            ->whereNotIn('category', array_values(array_unique($map)))
            ->update(['category' => 'other']);
    }

    public function down(): void
    {
        // The old scheme had two values collapsing into each of these, so a
        // faithful restore is not possible. Everything goes back to the
        // closest single value and the finer distinction has to be re-entered.
        DB::table('feed_items')->where('category', 'forage')->update(['category' => 'roughage']);
        DB::table('feed_items')->where('category', 'concentrate')->update(['category' => 'concentrate']);
        DB::table('feed_items')->where('category', 'supplement')->update(['category' => 'mineral']);
        DB::table('feed_items')->where('category', 'other')->update(['category' => 'other']);
    }
};
