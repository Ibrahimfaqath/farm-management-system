<?php

namespace Database\Seeders;

use App\Enums\MilkSession;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Farm;
use App\Models\MilkProduction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Daily milk records for the last 45 days.
 *
 * Scenario 6 from the specification: one animal shows a real drop in yield during
 * the most recent period. The system flags the change but does not diagnose it.
 */
class MilkProductionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $farm = Farm::firstOrFail();
            $workers = User::whereIn('role', ['worker', 'manager'])->get();

            $lactating = Animal::where('production_status', 'lactating')->get();

            // The declining animal: its yield falls noticeably in the last week.
            $decliningCode = $lactating->last()?->animal_code;

            $days = 45;

            for ($offset = $days; $offset >= 0; $offset--) {
                $date = now()->subDays($offset);
                $isRecent = $offset <= 7;

                foreach ($lactating as $index => $animal) {
                    // Base yield per animal, stable per animal with small variation.
                    $base = 14 + ($index % 7) * 1.4;
                    $variation = random_int(-8, 8) / 10;

                    $yield = $base + $variation;

                    if ($animal->animal_code === $decliningCode) {
                        $yield *= $isRecent ? 0.78 : 1.0;
                    }

                    MilkProduction::create([
                        'farm_id' => $farm->id,
                        'animal_id' => $animal->id,
                        'barn_id' => $animal->barn_id,
                        'production_date' => $date->toDateString(),
                        'session' => MilkSession::Mixed,
                        'liters' => round(max(0.5, $yield), 1),
                        'fat_percent' => $date->dayOfWeek % 7 === 0 ? round(fake()->randomFloat(2, 3.4, 4.6), 2) : null,
                        'protein_percent' => $date->dayOfWeek % 7 === 0 ? round(fake()->randomFloat(2, 2.8, 3.4), 2) : null,
                        'notes' => $animal->animal_code === $decliningCode && $isRecent
                            ? 'Produksi turun, perlu diperiksa lapangan.'
                            : null,
                        'recorded_by' => $workers->random()?->id,
                    ]);
                }
            }

            // A barn-level group entry to show the second supported mode.
            $barn = Barn::where('code', 'KAND-D')->first();

            if ($barn) {
                MilkProduction::create([
                    'farm_id' => $farm->id,
                    'barn_id' => $barn->id,
                    'production_date' => now()->subDay()->toDateString(),
                    'session' => MilkSession::Morning,
                    'liters' => 6.5,
                    'notes' => 'Catatan kelompok untuk anakan yang dipindah tangan.',
                    'recorded_by' => $workers->first()?->id,
                ]);
            }

            $this->command?->info('Produksi susu 46 hari terakhir dicatat untuk '.count($lactating).' ekor sapi laktasi.');
        });
    }
}
