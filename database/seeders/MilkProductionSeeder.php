<?php

namespace Database\Seeders;

use App\Enums\AnimalStatus;
use App\Enums\MilkSession;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\MilkProduction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Daily milk records for the last 45 days.
 *
 * Yield is anchored to the breed's realistic average instead of a single
 * arbitrary number, so a Jersey does not produce like a Holstein Friesian. The
 * most recent week of one high-yielding cow is deliberately lower: that is the
 * production decline the analytics screen is supposed to surface for a human to
 * investigate, not a diagnosis made by the system.
 *
 * Animals that have left the herd keep their historical records, but those
 * records stop before the exit date. A sold cow must not keep producing milk in
 * the current report.
 */
class MilkProductionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $farm = Farm::firstOrFail();
            $workers = User::query()
                ->whereIn('role', ['worker', 'manager'])
                ->orderBy('id')
                ->get();

            $active = Animal::query()
                ->active()
                ->lactating()
                ->with('breed')
                ->orderBy('animal_code')
                ->get();

            $exited = Animal::query()
                ->where('status', '!=', AnimalStatus::Active->value)
                ->lactating()
                ->with('breed')
                ->orderBy('animal_code')
                ->get();

            $decliningCode = $active->firstWhere('animal_code', 'LF-A001')?->animal_code
                ?? $active->first()?->animal_code;

            foreach ($active->values() as $index => $animal) {
                $base = $this->baseYield($animal);
                $isDeclining = $animal->animal_code === $decliningCode;

                for ($offset = 45; $offset >= 0; $offset--) {
                    $date = now()->subDays($offset);
                    $variation = (($this->hash($animal->animal_code) + ($offset * 7)) % 11 - 5) / 100;
                    $factor = 1 + $variation;

                    if ($isDeclining && $offset <= 7) {
                        $factor *= 0.68;
                    }

                    $this->createRecord(
                        $farm,
                        $animal,
                        $date->toDateString(),
                        round(max(2.5, $base * $factor), 1),
                        $offset,
                        $isDeclining && $offset <= 7,
                        $workers,
                        $index,
                    );
                }
            }

            foreach ($exited->values() as $index => $animal) {
                $base = $this->baseYield($animal);
                $exitDate = $animal->exit_date ?? now()->subDays(90);

                for ($offset = 30; $offset >= 1; $offset--) {
                    $date = $exitDate->copy()->subDays($offset);
                    $variation = (($this->hash($animal->animal_code) + ($offset * 5)) % 9 - 4) / 100;

                    $this->createRecord(
                        $farm,
                        $animal,
                        $date->toDateString(),
                        round(max(2.5, $base * (1 + $variation)), 1),
                        $offset,
                        false,
                        $workers,
                        $index,
                    );
                }
            }
        });
    }

    private function createRecord(
        Farm $farm,
        Animal $animal,
        string $date,
        float $liters,
        int $offset,
        bool $declining,
        Collection $workers,
        int $index,
    ): void {
        $breedName = $animal->breed?->name ?? '';
        $isButterfatBreed = str_contains($breedName, 'Jersey') || str_contains($breedName, 'Guernsey');
        $isSwissBreed = str_contains($breedName, 'Brown Swiss');

        MilkProduction::create([
            'farm_id' => $farm->id,
            'animal_id' => $animal->id,
            'barn_id' => $animal->barn_id,
            'production_date' => $date,
            'session' => MilkSession::Mixed,
            'liters' => $liters,
            'fat_percent' => $offset % 7 === 0
                ? ($isButterfatBreed ? 4.7 : ($isSwissBreed ? 3.9 : 3.7)) + (($this->hash($animal->animal_code) + $offset) % 3) / 10
                : null,
            'protein_percent' => $offset % 7 === 0
                ? ($isButterfatBreed ? 3.6 : ($isSwissBreed ? 3.4 : 3.2)) + (($this->hash($animal->animal_code) + $offset) % 2) / 10
                : null,
            'notes' => $declining
                ? 'Produksi turun dibanding minggu sebelumnya; perlu pemeriksaan kondisi ambing dan pakan.'
                : null,
            'recorded_by' => $workers->isNotEmpty() ? $workers->get($index % $workers->count())?->id : null,
        ]);
    }

    private function baseYield(Animal $animal): float
    {
        $average = (float) ($animal->breed?->average_milk_liters ?? 15.0);
        $factor = 0.85 + (($this->hash($animal->animal_code) % 31) / 100);

        return $average * $factor;
    }

    private function hash(string $value): int
    {
        return (int) sprintf('%u', crc32($value));
    }
}
