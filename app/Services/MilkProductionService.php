<?php

namespace App\Services;

use App\Enums\ProductionStatus;
use App\Models\Animal;
use App\Models\MilkProduction;
use App\Models\User;
use App\Support\Number;
use Illuminate\Support\Facades\DB;

/**
 * Records milk production. A record belongs either to one animal or to a barn
 * group, never both, so totals are never counted twice.
 */
class MilkProductionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{
     *     farm_id: int,
     *     animal_id?: int|null,
     *     barn_id?: int|null,
     *     production_date: string,
     *     session: string,
     *     liters: float,
     *     fat_percent?: float|null,
     *     protein_percent?: float|null,
     *     notes?: string|null
     * }  $data
     */
    public function record(array $data, ?User $user = null): MilkProduction
    {
        return DB::transaction(function () use ($data, $user): MilkProduction {
            $production = MilkProduction::create([
                'farm_id' => $data['farm_id'],
                'animal_id' => $data['animal_id'] ?? null,
                'barn_id' => $data['animal_id'] ? null : ($data['barn_id'] ?? null),
                'production_date' => $data['production_date'],
                'session' => $data['session'],
                'liters' => $data['liters'],
                'fat_percent' => $data['fat_percent'] ?? null,
                'protein_percent' => $data['protein_percent'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user?->getKey(),
            ]);

            $this->audit->log(
                'production.recorded',
                $production,
                'Produksi susu dicatat: '.Number::format((float) $data['liters'], 2).' L',
                $production->subjectLabel(),
            );

            return $production;
        });
    }

    /**
     * Bulk entry for a barn group.
     *
     * @param  list<array{animal_id: int, session: string, liters: float, fat_percent?: float|null}>  $lines
     */
    public function recordBatch(int $farmId, string $date, array $lines, ?User $user = null): int
    {
        $count = 0;

        DB::transaction(function () use ($farmId, $date, $lines, $user, &$count): void {
            foreach ($lines as $line) {
                $animal = Animal::query()->find($line['animal_id']);

                if (! $animal) {
                    continue;
                }

                $this->record([
                    'farm_id' => $farmId,
                    'animal_id' => $animal->getKey(),
                    'production_date' => $date,
                    'session' => $line['session'],
                    'liters' => $line['liters'],
                    'fat_percent' => $line['fat_percent'] ?? null,
                ], $user);

                $count++;
            }
        });

        return $count;
    }

    /**
     * Move an animal between production states. Kept explicit so operators can
     * dry a cow off without inventing a medical reason.
     */
    public function changeProductionStatus(Animal $animal, ProductionStatus $status, ?string $note = null, ?User $user = null): void
    {
        $from = $animal->production_status;

        $animal->forceFill(['production_status' => $status])->save();

        $this->audit->log(
            'production.status_changed',
            $animal,
            "Status produksi {$animal->animal_code}: {$from->label()} → {$status->label()}".($note ? " ({$note})" : ''),
            $animal->animal_code,
        );
    }
}
