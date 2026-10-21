<?php

namespace App\Services;

use App\Enums\AcquisitionType;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Models\Animal;
use App\Models\BirthRecord;
use App\Models\PregnancyCheck;
use App\Models\User;

/**
 * Applies breeding, pregnancy and birth events to the animal's status facets.
 * Pregnancy is never inferred from mating alone: only a pregnancy check with a
 * positive result moves the animal into a pregnant state.
 */
class BreedingService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Mating event: the animal is bred and awaiting confirmation, not pregnant.
     */
    public function recordMating(Animal $animal, ?int $breedingRecordId, User $user): void
    {
        $animal->forceFill([
            'breeding_status' => BreedingStatus::Bred,
        ])->save();

        $this->audit->log(
            'breeding.mating_recorded',
            $animal,
            "Perkawinan tercatat untuk {$animal->animal_code}; menunggu pemeriksaan kehamilan",
            $animal->animal_code,
            ['breeding_record_id' => $breedingRecordId],
        );
    }

    /**
     * Pregnancy check result. A negative or unknown result returns the animal to
     * the open cycle.
     */
    public function applyPregnancyCheck(PregnancyCheck $check, User $user): void
    {
        $animal = $check->animal;

        $status = match ($check->result->value) {
            'confirmed_pregnant' => BreedingStatus::ConfirmedPregnant,
            'pregnant' => BreedingStatus::Pregnant,
            default => BreedingStatus::Open,
        };

        $updates = ['breeding_status' => $status];

        // A confirmed pregnant lactating cow stays lactating; nothing is assumed.
        $animal->forceFill($updates)->save();

        $this->audit->log(
            'breeding.pregnancy_checked',
            $animal,
            "Pemeriksaan kehamilan {$animal->animal_code}: {$check->result->label()}",
            $animal->animal_code,
            ['check_id' => $check->getKey(), 'result' => $check->result->value],
        );
    }

    /**
     * Birth event: registers the calf, links genealogy and resets the dam.
     *
     * @param  array{
     *     dam_id: int,
     *     sire_id?: int|null,
     *     pregnancy_check_id?: int|null,
     *     birth_date: string,
     *     birth_type: string,
     *     birth_weight_kg?: float|null,
     *     animal_code?: string|null,
     *     calf_sex?: string|null,
     *     animal_id?: int|null
     * }  $data
     */
    public function recordBirth(array $data, User $user): BirthRecord
    {
        $dam = Animal::query()->findOrFail($data['dam_id']);

        $birth = new BirthRecord([
            'dam_id' => $dam->getKey(),
            'sire_id' => $data['sire_id'] ?? null,
            'pregnancy_check_id' => $data['pregnancy_check_id'] ?? null,
            'birth_date' => $data['birth_date'],
            'birth_type' => $data['birth_type'],
            'birth_weight_kg' => $data['birth_weight_kg'] ?? null,
            'animal_id' => $data['animal_id'] ?? null,
            'created_by' => $user->getKey(),
        ]);

        $birth->save();

        $dam->forceFill([
            'breeding_status' => BreedingStatus::Open,
            'production_status' => ProductionStatus::Lactating,
        ])->save();

        if ($birth->animal) {
            $birth->animal->forceFill([
                'dam_id' => $dam->getKey(),
                'sire_id' => $data['sire_id'] ?? null,
                'acquisition_type' => AcquisitionType::Birth,
                'acquisition_date' => $data['birth_date'],
            ])->save();
        }

        $this->audit->log(
            'breeding.birth_recorded',
            $dam,
            "Kelahiran pada {$dam->animal_code} dicatat ({$birth->birth_type->label()})",
            $birth->animal?->animal_code,
            ['birth_id' => $birth->getKey(), 'calf' => $birth->animal?->animal_code],
        );

        return $birth->fresh();
    }
}
