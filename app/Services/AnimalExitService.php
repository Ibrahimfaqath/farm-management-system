<?php

namespace App\Services;

use App\Enums\AnimalStatus;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records an animal leaving the herd. The animal row is kept so every historical
 * health, breeding, production and financial record stays queryable.
 */
class AnimalExitService
{
    /**
     * @param  array{exit_type: string, exit_date: string, exit_value?: ?float, exit_reference?: ?string, exit_notes?: ?string, reason: string}  $data
     * @return array{message: string, status: AnimalStatus}
     */
    public function record(Animal $animal, array $data, ?User $user = null): array
    {
        $status = AnimalStatus::from($data['exit_type']);

        return DB::transaction(function () use ($animal, $data, $status, $user): array {
            $animal->update([
                'status' => $status,
                'exit_date' => $data['exit_date'],
                'exit_type' => $status->value,
                'exit_value' => $data['exit_value'] ?? 0,
                'exit_reference' => $data['exit_reference'] ?? null,
                'exit_notes' => trim(($data['exit_notes'] ?? '')."\nAlasan: ".$data['reason']) ?: null,
            ]);

            app(AuditLogger::class)->log(
                action: "animal.{$status->value}",
                subject: $animal,
                description: "Ternak {$animal->animal_code} ditandai {$status->label()}",
                reference: $data['exit_reference'] ?? null,
                properties: [
                    'reason' => $data['reason'],
                    'exit_date' => $data['exit_date'],
                    'exit_value' => $data['exit_value'] ?? 0,
                    'user' => $user?->name,
                ],
            );

            return [
                'status' => $status,
                'message' => "Ternak {$animal->animal_code} ditandai {$status->label()}. Seluruh riwayat tetap tersimpan.",
            ];
        });
    }
}
