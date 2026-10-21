<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The farm only keeps dairy cattle, but older demo data still listed beef
 * breeds beside the dairy ones. Deleting them would break the animals that
 * already point at those rows, so they are retired instead: the breed list and
 * the animal form only offer active breeds, while historical animal records
 * keep the name they were entered with.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('breeds')
            ->whereIn('name', [
                'Limosin',
                'Brahman',
                'Madura',
                'Simmental',
                'Angus',
                'Hereford',
                'Charolais',
                'Belgian Blue',
                'Santa Gertrudis',
            ])
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        // Reactivating a retired breed would put beef breeds back in the
        // livestock form, so the retirement is intentionally one-way.
    }
};
