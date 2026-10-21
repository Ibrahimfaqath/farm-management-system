<?php

namespace Database\Seeders;

use App\Enums\BirthType;
use App\Enums\BreedingRecordType;
use App\Enums\BreedingStatus;
use App\Enums\HealthRecordStatus;
use App\Enums\HealthRecordType;
use App\Enums\HealthSeverity;
use App\Enums\PregnancyCheckMethod;
use App\Enums\PregnancyResult;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\BirthRecord;
use App\Models\BreedingRecord;
use App\Models\HealthRecord;
use App\Models\PregnancyCheck;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Vaccination;
use App\Services\BreedingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Health, breeding and birth history with real business relationships.
 */
class HerdHistorySeeder extends Seeder
{
    public function run(BreedingService $breeding): void
    {
        DB::transaction(function () use ($breeding): void {
            $vet = User::where('email', 'vet@farm.test')->firstOrFail();
            $manager = User::where('email', 'manager@farm.test')->firstOrFail();

            $lactating = Animal::where('production_status', 'lactating')->orderBy('animal_code')->get();
            $heifers = Animal::where('production_status', 'heifer')->orderBy('animal_code')->get();
            $bulls = Animal::where('sex', 'male')->orderBy('animal_code')->get();
            $calves = Animal::where('production_status', 'calf')->orderBy('animal_code')->get();

            $this->healthRecords($lactating, $heifers, $calves, $vet);
            $this->vaccinations($lactating->merge($heifers)->merge($calves), $vet);
            $this->treatments($lactating, $vet);

            $this->breedingHistory($lactating, $heifers, $bulls, $breeding, $manager, $vet);
            $this->birthHistory($lactating, $bulls, $calves, $breeding, $manager);
        });

        $this->command?->info('Rekam kesehatan, vaksinasi, pengobatan, perkawinan, pemeriksaan kehamilan, dan kelahiran dibuat.');
    }

    private function healthRecords($lactating, $heifers, $calves, User $vet): void
    {
        $observations = [
            ['Penurunan nafsu makan setelah pagi', null, HealthSeverity::Mild],
            ['Kurang aktif dan sering berdeham', null, HealthSeverity::Moderate],
            ['Bengkak ringan pada ambing kiri', 'Mastitis subklinis', HealthSeverity::Moderate],
            ['Nafsu makan normal, produksi turun 1 liter', null, HealthSeverity::Mild],
        ];

        $index = 0;

        foreach ($lactating->take(6) as $animal) {
            [$symptoms, $diagnosis, $severity] = $observations[$index % count($observations)];
            $index++;

            $resolved = $index % 2 === 0;

            HealthRecord::create([
                'animal_id' => $animal->id,
                'record_date' => now()->subDays(random_int(3, 40))->toDateString(),
                'record_type' => $diagnosis ? HealthRecordType::Diagnosis : HealthRecordType::Observation,
                'severity' => $severity,
                'symptoms' => $symptoms,
                'diagnosis' => $diagnosis,
                'status' => $resolved ? HealthRecordStatus::Resolved : HealthRecordStatus::Open,
                'follow_up_date' => $resolved ? null : now()->addDays(random_int(2, 7))->toDateString(),
                'resolved_date' => $resolved ? now()->subDays(random_int(1, 5))->toDateString() : null,
                'cost' => $diagnosis ? random_int(150_000, 900_000) : 0,
                'veterinarian_id' => $diagnosis ? $vet->id : null,
                'notes' => $resolved ? 'Gejala mereda setelah pretreatment.' : 'Pantau kondisi dua hari ke depan.',
            ]);
        }

        foreach ($calves->take(3) as $calf) {
            HealthRecord::create([
                'animal_id' => $calf->id,
                'record_date' => now()->subDays(random_int(2, 20))->toDateString(),
                'record_type' => HealthRecordType::Checkup,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Batuk ringan setelah pindah kandang',
                'diagnosis' => 'Infeksi saluran pernapasan atas ringan',
                'status' => HealthRecordStatus::Monitoring,
                'cost' => random_int(50_000, 200_000),
                'veterinarian_id' => $vet->id,
            ]);
        }

        foreach ($heifers->take(2) as $heifer) {
            HealthRecord::create([
                'animal_id' => $heifer->id,
                'record_date' => now()->subDays(random_int(10, 50))->toDateString(),
                'record_type' => HealthRecordType::Routine,
                'severity' => null,
                'symptoms' => 'Pemeriksaan rutin, kondisi baik',
                'status' => HealthRecordStatus::Resolved,
                'resolved_date' => now()->subDays(random_int(9, 49))->toDateString(),
                'cost' => 100_000,
                'veterinarian_id' => $vet->id,
            ]);
        }
    }

    private function vaccinations($animals, User $vet): void
    {
        $vaccines = [
            ['Brucellosis', 'VXC-BR-2401', 180],
            ['FMD', 'VXC-FMD-2403', 180],
            ['Haemorrhagic Septicaemia', 'VXC-HS-2402', 365],
            ['BVD', 'VXC-BVD-2404', 365],
        ];

        foreach ($animals as $index => $animal) {
            [$name, $batch, $interval] = $vaccines[$index % count($vaccines)];
            $administered = now()->subDays(random_int(30, 150));

            Vaccination::create([
                'animal_id' => $animal->id,
                'vaccine_name' => $name,
                'batch_number' => $batch,
                'administered_date' => $administered->toDateString(),
                'next_due_date' => $index % 7 === 0
                    ? $administered->copy()->addDays($interval)->subDays(20)->toDateString()
                    : $administered->copy()->addDays($interval)->toDateString(),
                'dose' => '2 ml IM',
                'route' => 'IM',
                'cost' => random_int(35_000, 150_000),
                'administered_by' => 'Ahmad (petugas)',
                'veterinarian_id' => $vet->id,
            ]);
        }
    }

    private function treatments($lactating, User $vet): void
    {
        $medications = [
            ['Oxytetracycline 20%', '20 ml IM', 'IM', '1x sehari', 3, 4],
            ['Penicillin Prokain', '10 ml SC', 'SC', '2x sehari', 3, 5],
            ['Ketoprofen', '5 ml IM', 'IM', '1x sehari', 3, 2],
            ['Vitamin B Complex', '10 ml SC', 'SC', '1x sehari', 0, 0],
        ];

        foreach ($lactating->take(5) as $index => $animal) {
            [$name, $dosage, $route, $frequency, $withdrawal, $days] = $medications[$index % count($medications)];
            $start = now()->subDays(random_int(4, 20));

            // A treatment belongs to a health record in practice, and the
            // treatments list only offers a row action when one exists. Leaving
            // this null left every row in that list with nothing to click.
            $healthRecord = HealthRecord::firstOrCreate(
                ['animal_id' => $animal->id],
                [
                    'record_number' => 'HC-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'recorded_at' => $start->copy()->subDay(),
                    'symptoms' => 'Susah berdiri dan tampak meringis pada sendi kaki belakang',
                    'diagnosis' => $name === 'Ketoprofen' ? 'Nyeri sendi ringan' : 'Defisiensi vitamin',
                    'status' => HealthRecordStatus::Resolved,
                    'recorded_by' => $vet->id,
                ],
            );

            Treatment::create([
                'animal_id' => $animal->id,
                'health_record_id' => $healthRecord->id,
                'medication_name' => $name,
                'dosage' => $dosage,
                'route' => $route,
                'frequency' => $frequency,
                'treatment_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($days)->toDateString(),
                'withdrawal_period_days' => $withdrawal,
                'cost' => random_int(85_000, 650_000),
                'veterinarian_id' => $vet->id,
            ]);
        }
    }

    private function breedingHistory($lactating, $heifers, $bulls, BreedingService $breeding, User $manager, User $vet): void
    {
        $bull = $bulls->first();

        // Confirmed pregnancies with due dates in the near future.
        foreach (Animal::where('breeding_status', BreedingStatus::ConfirmedPregnant)->get() as $pregnant) {
            $record = BreedingRecord::create([
                'animal_id' => $pregnant->id,
                'record_type' => BreedingRecordType::Insemination,
                'breeding_date' => now()->subDays(120)->toDateString(),
                'bull_id' => $bull?->id,
                'semen_code' => 'JRS-02',
                'semen_batch' => 'SB-2406-11',
                'inseminator' => 'Pak Joko',
                'heat_signs' => 'Gejala birahi terlihat pada pagi hari.',
                'created_by' => $manager->id,
            ]);

            $check = PregnancyCheck::create([
                'animal_id' => $pregnant->id,
                'breeding_record_id' => $record->id,
                'check_date' => now()->subDays(60)->toDateString(),
                'method' => PregnancyCheckMethod::Rectal,
                'result' => PregnancyResult::ConfirmedPregnant,
                'estimated_gestation_days' => 60,
                'expected_due_date' => now()->addDays(5)->toDateString(),
                'fetus_count' => 1,
                'veterinarian_id' => $vet->id,
                'notes' => 'Kondisi ibu baik, denyut nadi dalam batas normal.',
            ]);

            $breeding->applyPregnancyCheck($check, $vet);
        }

        // Heifers with a mating record awaiting confirmation (pregnancy not assumed).
        foreach ($heifers->take(3) as $index => $heifer) {
            $record = BreedingRecord::create([
                'animal_id' => $heifer->id,
                'record_type' => BreedingRecordType::Insemination,
                'breeding_date' => now()->subDays(25 + $index * 3)->toDateString(),
                'bull_id' => $bull?->id,
                'semen_code' => $index % 2 === 0 ? 'BRF-01' : 'LIM-03',
                'semen_batch' => 'SB-2406-'.(11 + $index),
                'inseminator' => 'Pak Joko',
                'heat_signs' => 'Gejala birahi terlihat pada sore hari.',
                'created_by' => $manager->id,
            ]);

            $breeding->recordMating($heifer, $record->id, $manager);
        }

        // One heifer where the check came back negative.
        $recheck = $heifers->skip(3)->first();

        if ($recheck) {
            $record = BreedingRecord::create([
                'animal_id' => $recheck->id,
                'record_type' => BreedingRecordType::Insemination,
                'breeding_date' => now()->subDays(70)->toDateString(),
                'bull_id' => $bull?->id,
                'semen_code' => 'BRF-01',
                'inseminator' => 'Pak Joko',
                'heat_signs' => 'Gejala birahi tidak dominan.',
                'created_by' => $manager->id,
            ]);

            $check = PregnancyCheck::create([
                'animal_id' => $recheck->id,
                'breeding_record_id' => $record->id,
                'check_date' => now()->subDays(45)->toDateString(),
                'method' => PregnancyCheckMethod::Rectal,
                'result' => PregnancyResult::NotPregnant,
                'notes' => 'Belum terdeteksi kehamilan. Menunggu siklus birahi berikutnya.',
                'veterinarian_id' => $vet->id,
            ]);

            $breeding->applyPregnancyCheck($check, $vet);
        }

        // Heat observation only — must not change breeding status.
        BreedingRecord::create([
            'animal_id' => $lactating->first()?->id,
            'record_type' => BreedingRecordType::HeatObserved,
            'breeding_date' => now()->subDays(6)->toDateString(),
            'heat_signs' => 'Gejala birahi ringan tercatat oleh petugas.',
            'expected_next_heat_date' => now()->addDays(15)->toDateString(),
            'created_by' => $manager->id,
        ]);
    }

    private function birthHistory($lactating, $bulls, $calves, BreedingService $breeding, User $manager): void
    {
        $bull = $bulls->first();

        foreach ($calves as $index => $calf) {
            $dam = $calf->dam ?? $lactating->get($index % max(1, $lactating->count()));
            $birthDate = $calf->date_of_birth ?? now()->subDays(120);

            BirthRecord::create([
                'dam_id' => $dam->id,
                'sire_id' => $bull?->id,
                'animal_id' => $calf->id,
                'birth_date' => $birthDate->toDateString(),
                'birth_time' => '0'.random_int(3, 8).':'.str_pad((string) random_int(0, 59), 2, '0', STR_PAD_LEFT),
                'birth_type' => $index % 4 === 3 ? BirthType::Assisted : BirthType::Normal,
                'birth_weight_kg' => random_int(28, 42),
                'colostrum_intake' => 'Dalam 2 jam pertama, sekitar 10% berat badan',
                'calf_sex' => $calf->sex === Sex::Male ? 1 : 0,
                'assistance_notes' => $index % 4 === 3 ? 'Perlu bantuan satu orang.' : null,
                'created_by' => $manager->id,
            ]);
        }
    }
}
