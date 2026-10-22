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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Health, breeding and birth history with real business relationships.
 *
 * The records are written with dates that make sense together: a mastitis case
 * has a treatment and a withdrawal period, a pregnancy check follows an
 * insemination, and a calf's birth record points to the dam and sire that are
 * already in the herd. Nothing is created just to make a table non-empty.
 */
class HerdHistorySeeder extends Seeder
{
    public function run(BreedingService $breeding): void
    {
        DB::transaction(function () use ($breeding): void {
            $vet = User::where('email', 'vet@farm.test')->firstOrFail();
            $manager = User::where('email', 'manager@farm.test')->firstOrFail();

            $lactating = Animal::query()
                ->active()
                ->where('production_status', 'lactating')
                ->orderBy('animal_code')
                ->get();

            $heifers = Animal::query()
                ->active()
                ->where('production_status', 'heifer')
                ->orderBy('animal_code')
                ->get();

            $calves = Animal::query()
                ->active()
                ->where('production_status', 'calf')
                ->orderBy('animal_code')
                ->get();

            $bulls = Animal::query()
                ->active()
                ->where('sex', 'male')
                ->orderBy('animal_code')
                ->get();

            $this->healthRecords($lactating, $heifers, $calves, $vet, $manager);
            $this->vaccinations(
                $lactating->merge($heifers)->merge($calves)->merge($bulls),
                $vet,
            );
            $this->treatments($vet);
            $this->breedingHistory($lactating, $heifers, $bulls, $breeding, $manager, $vet);
            $this->birthHistory($calves, $bulls, $manager);
        });
    }

    /**
     * A realistic mix of observations, diagnoses and routine checks.
     *
     * @param  Collection<int, Animal>  $lactating
     * @param  Collection<int, Animal>  $heifers
     * @param  Collection<int, Animal>  $calves
     */
    private function healthRecords(
        Collection $lactating,
        Collection $heifers,
        Collection $calves,
        User $vet,
        User $manager,
    ): void {
        $cowCases = [
            [
                'type' => HealthRecordType::Observation,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Nafsu makan menurun sejak pagi, suhu tubuh 38,2 °C.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Open,
                'follow_up_days' => 2,
                'cost' => 0,
                'notes' => 'Dipantau oleh petugas kandang; belum perlu pengobatan.',
            ],
            [
                'type' => HealthRecordType::Diagnosis,
                'severity' => HealthSeverity::Moderate,
                'symptoms' => 'Ambing kiri bengkak, susu menggumpal, dan sapi gelisah saat diperah.',
                'diagnosis' => 'Mastitis subklinis',
                'status' => HealthRecordStatus::Monitoring,
                'follow_up_days' => 3,
                'cost' => 275_000,
                'notes' => 'Susu dari ambing yang sakit dipisahkan dan tidak dikirim ke koperasi.',
            ],
            [
                'type' => HealthRecordType::Diagnosis,
                'severity' => HealthSeverity::Moderate,
                'symptoms' => 'Luka pada kuku kaki belakang kanan dan sapi tampak pincang.',
                'diagnosis' => 'Laminitis ringan',
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 450_000,
                'notes' => 'Kandang dibersihkan dan alas jerami ditambah.',
            ],
            [
                'type' => HealthRecordType::Checkup,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Pemeriksaan rutin 14 hari pasca kelahiran.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 75_000,
                'notes' => 'Involusi uterus berjalan normal.',
            ],
            [
                'type' => HealthRecordType::Diagnosis,
                'severity' => HealthSeverity::Serious,
                'symptoms' => 'Demam tinggi, tidak mau makan, dan keluar lendir keruh dari vulva.',
                'diagnosis' => 'Metritis ringan',
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 650_000,
                'notes' => 'Diobati oleh dokter hewan dan dipisahkan selama masa pengobatan.',
            ],
            [
                'type' => HealthRecordType::Observation,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Batuk ringan setelah hujan sore.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 0,
                'notes' => 'Membaik setelah kandang dikeringkan.',
            ],
            [
                'type' => HealthRecordType::Diagnosis,
                'severity' => HealthSeverity::Moderate,
                'symptoms' => 'Perut kembung dan nafsu makan turun setelah pemberian silase.',
                'diagnosis' => 'Bloat ringan',
                'status' => HealthRecordStatus::Monitoring,
                'follow_up_days' => 2,
                'cost' => 300_000,
                'notes' => 'Pemberian pakan hijau ditingkatkan sementara.',
            ],
            [
                'type' => HealthRecordType::Routine,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Pemeriksaan kebuntingan rutin dan penimbangan berat badan.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 0,
                'notes' => 'Kondisi tubuh baik, skor BCS 3,25.',
            ],
            [
                'type' => HealthRecordType::Diagnosis,
                'severity' => HealthSeverity::Moderate,
                'symptoms' => 'Kaki depan bengkak pada bagian koroner.',
                'diagnosis' => 'Radang kuku',
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 385_000,
                'notes' => 'Dilakukan perawatan kuku dan pemberian antibiotik.',
            ],
        ];

        foreach ($lactating->take(9) as $index => $animal) {
            $case = $cowCases[$index % count($cowCases)];

            $this->createHealthRecord($animal, $case, $index * 4, $vet, $manager);
        }

        $calfCases = [
            [
                'type' => HealthRecordType::Checkup,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Diare ringan dan nafsu susu menurun.',
                'diagnosis' => 'Dispepsia pedet',
                'status' => HealthRecordStatus::Monitoring,
                'follow_up_days' => 2,
                'cost' => 150_000,
                'notes' => 'Diberikan oralit dan susu tetap diberikan dalam porsi kecil.',
            ],
            [
                'type' => HealthRecordType::Checkup,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Pemeriksaan rutin pertumbuhan pedet.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 0,
                'notes' => 'Berat badan naik sesuai kurva pertumbuhan.',
            ],
            [
                'type' => HealthRecordType::Observation,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Sedikit lecet pada lutut depan.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 0,
                'notes' => 'Luka dibersihkan dan alas kandang diganti.',
            ],
        ];

        foreach ($calves->take(3) as $index => $animal) {
            $this->createHealthRecord($animal, $calfCases[$index], 5 + $index * 3, $vet, $manager);
        }

        $heiferCases = [
            [
                'type' => HealthRecordType::Routine,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Pemeriksaan berat badan dan pertumbuhan dara.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 0,
                'notes' => 'Siap masuk program penggemukan sebelum dikawinkan.',
            ],
            [
                'type' => HealthRecordType::Observation,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Nafsu makan sedikit menurun setelah pergantian pakan.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 0,
                'notes' => 'Adaptasi pakan berjalan normal.',
            ],
            [
                'type' => HealthRecordType::Routine,
                'severity' => HealthSeverity::Mild,
                'symptoms' => 'Pemeriksaan kesehatan sebelum inseminasi pertama.',
                'diagnosis' => null,
                'status' => HealthRecordStatus::Resolved,
                'follow_up_days' => null,
                'cost' => 0,
                'notes' => 'Sehat, organ reproduksi normal.',
            ],
        ];

        foreach ($heifers->take(3) as $index => $animal) {
            $this->createHealthRecord($animal, $heiferCases[$index], 16 + $index * 5, $vet, $manager);
        }
    }

    /**
     * @param  array{type: HealthRecordType, severity: HealthSeverity, symptoms: string, diagnosis: ?string, status: HealthRecordStatus, follow_up_days: ?int, cost: int, notes: string}  $case
     */
    private function createHealthRecord(
        Animal $animal,
        array $case,
        int $daysAgo,
        User $vet,
        User $manager,
    ): HealthRecord {
        $recordDate = now()->subDays(10 + $daysAgo);

        return HealthRecord::create([
            'animal_id' => $animal->id,
            'record_date' => $recordDate->toDateString(),
            'record_type' => $case['type'],
            'severity' => $case['severity'],
            'symptoms' => $case['symptoms'],
            'diagnosis' => $case['diagnosis'],
            'notes' => $case['notes'],
            'status' => $case['status'],
            'follow_up_date' => $case['follow_up_days'] !== null
                ? $recordDate->copy()->addDays($case['follow_up_days'])->toDateString()
                : null,
            'resolved_date' => $case['status'] === HealthRecordStatus::Resolved
                ? $recordDate->copy()->addDays(3)->toDateString()
                : null,
            'cost' => $case['cost'],
            'veterinarian_id' => $case['diagnosis'] !== null || $case['type'] !== HealthRecordType::Observation
                ? $vet->id
                : null,
            'created_by' => $manager->id,
        ]);
    }

    /**
     * Every active animal has a vaccination history. A few are overdue so the
     * attention list has a genuine, explainable item.
     *
     * @param  Collection<int, Animal>  $animals
     */
    private function vaccinations(Collection $animals, User $vet): void
    {
        $vaccines = [
            ['Brucellosis', 'VXC-BR-2601', 180, '2 ml', 'subkutan'],
            ['Penyakit Mulut dan Kuku (PMK)', 'VXC-PMK-2602', 180, '2 ml', 'intramuskular'],
            ['Septicaemia Epizootica', 'VXC-SE-2601', 365, '2 ml', 'subkutan'],
            ['Bovine Viral Diarrhea', 'VXC-BVD-2601', 365, '2 ml', 'intramuskular'],
            ['Antraks', 'VXC-ANT-2601', 365, '1 ml', 'subkutan'],
        ];

        foreach ($animals->values() as $index => $animal) {
            [$name, $batch, $interval, $dose, $route] = $vaccines[$index % count($vaccines)];

            $administered = now()->subDays(25 + (($index * 3) % 70));
            $nextDue = $index % 11 === 0
                ? now()->subDays(2 + ($index % 5))
                : $administered->copy()->addDays($interval);

            Vaccination::create([
                'animal_id' => $animal->id,
                'vaccine_name' => $name,
                'batch_number' => $batch,
                'administered_date' => $administered->toDateString(),
                'next_due_date' => $nextDue->toDateString(),
                'dose' => $dose,
                'route' => $route,
                'cost' => 25_000 + ($index % 6) * 2_500,
                'administered_by' => $vet->name,
                'veterinarian_id' => $vet->id,
                'notes' => 'Vaksinasi rutin semester II 2026.',
            ]);
        }
    }

    private function treatments(User $vet): void
    {
        $records = HealthRecord::query()
            ->whereNotNull('diagnosis')
            ->whereIn('status', [HealthRecordStatus::Monitoring, HealthRecordStatus::Resolved])
            ->orderBy('record_date')
            ->get();

        foreach ($records as $index => $record) {
            $protocol = match (true) {
                str_contains((string) $record->diagnosis, 'Mastitis') => [
                    'Amoksisilin 15%', '20 ml', 'IM', '2 kali sehari', 3, 4, 275_000,
                ],
                str_contains((string) $record->diagnosis, 'Metritis') => [
                    'Oxytetracycline 20%', '20 ml', 'IM', '1 kali sehari', 5, 7, 650_000,
                ],
                str_contains((string) $record->diagnosis, 'Laminitis'),
                str_contains((string) $record->diagnosis, 'Radang kuku') => [
                    'Ketoprofen 10%', '10 ml', 'IM', '1 kali sehari', 3, 0, 385_000,
                ],
                str_contains((string) $record->diagnosis, 'Bloat') => [
                    'Mineral oil', '250 ml', 'oral', '1 kali', 1, 0, 120_000,
                ],
                str_contains((string) $record->diagnosis, 'Dispepsia') => [
                    'Oralit pedet', '500 ml', 'oral', '2 kali sehari', 2, 0, 75_000,
                ],
                default => [
                    'Vitamin B Complex', '10 ml', 'IM', '1 kali', 1, 0, 90_000,
                ],
            };

            [$medication, $dosage, $route, $frequency, $days, $withdrawal, $cost] = $protocol;
            $start = $record->record_date->copy()->addDay();

            Treatment::create([
                'animal_id' => $record->animal_id,
                'health_record_id' => $record->id,
                'medication_name' => $medication,
                'dosage' => $dosage,
                'route' => $route,
                'frequency' => $frequency,
                'treatment_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($days - 1)->toDateString(),
                'withdrawal_period_days' => $withdrawal,
                'cost' => $cost + $index * 5_000,
                'veterinarian_id' => $vet->id,
                'notes' => 'Pengobatan dicatat oleh dokter hewan; susu dipisahkan selama masa henti obat.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Animal>  $lactating
     * @param  Collection<int, Animal>  $heifers
     * @param  Collection<int, Animal>  $bulls
     */
    private function breedingHistory(
        Collection $lactating,
        Collection $heifers,
        Collection $bulls,
        BreedingService $breeding,
        User $manager,
        User $vet,
    ): void {
        $pregnantCows = Animal::query()
            ->active()
            ->where('breeding_status', 'confirmed_pregnant')
            ->orderBy('animal_code')
            ->get();

        foreach ($pregnantCows->values() as $index => $cow) {
            $breedingDate = now()->subDays(110 + ($index * 7));
            $record = BreedingRecord::create([
                'animal_id' => $cow->id,
                'record_type' => BreedingRecordType::Insemination,
                'breeding_date' => $breedingDate->toDateString(),
                'bull_id' => null,
                'semen_code' => $this->semenCode($cow->breed?->name),
                'semen_batch' => 'SB-'.now()->format('ym').'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'inseminator' => 'Pak Joko',
                'heat_signs' => 'Birahi pagi, diam saat dinaiki, vulva bengkak.',
                'expected_next_heat_date' => $breedingDate->copy()->addDays(21)->toDateString(),
                'notes' => 'Inseminasi buatan menggunakan semen beku bersertifikat.',
                'created_by' => $manager->id,
            ]);

            $checkDate = now()->subDays(55 + ($index * 4));
            $check = PregnancyCheck::create([
                'animal_id' => $cow->id,
                'breeding_record_id' => $record->id,
                'check_date' => $checkDate->toDateString(),
                'method' => $index % 2 === 0 ? PregnancyCheckMethod::Rectal : PregnancyCheckMethod::Ultrasound,
                'result' => PregnancyResult::ConfirmedPregnant,
                'estimated_gestation_days' => (int) $breedingDate->diffInDays($checkDate),
                'expected_due_date' => now()->addDays(8 + ($index * 3))->toDateString(),
                'fetus_count' => 1,
                'notes' => 'Kebuntingan terkonfirmasi; sapi tetap diproduksi sesuai kondisi.',
                'veterinarian_id' => $vet->id,
                'created_by' => $manager->id,
            ]);

            $breeding->applyPregnancyCheck($check, $vet);
        }

        foreach ($heifers->take(4)->values() as $index => $heifer) {
            $record = BreedingRecord::create([
                'animal_id' => $heifer->id,
                'record_type' => BreedingRecordType::Insemination,
                'breeding_date' => now()->subDays(20 + ($index * 4))->toDateString(),
                'bull_id' => null,
                'semen_code' => $this->semenCode($heifer->breed?->name),
                'semen_batch' => 'SB-'.now()->format('ym').'-'.str_pad((string) ($index + 10), 2, '0', STR_PAD_LEFT),
                'inseminator' => 'Pak Joko',
                'heat_signs' => 'Birahi jelas pada pagi hari.',
                'expected_next_heat_date' => now()->subDays(20 + ($index * 4))->addDays(21)->toDateString(),
                'notes' => 'Inseminasi pertama; menunggu pemeriksaan kebuntingan.',
                'created_by' => $manager->id,
            ]);

            $breeding->recordMating($heifer, $record->id, $manager);
        }

        $negativeHeifer = $heifers->slice(4, 1)->first();

        if ($negativeHeifer !== null) {
            $record = BreedingRecord::create([
                'animal_id' => $negativeHeifer->id,
                'record_type' => BreedingRecordType::Insemination,
                'breeding_date' => now()->subDays(70)->toDateString(),
                'bull_id' => null,
                'semen_code' => $this->semenCode($negativeHeifer->breed?->name),
                'semen_batch' => 'SB-'.now()->format('ym').'-15',
                'inseminator' => 'Pak Joko',
                'heat_signs' => 'Birahi pagi, refleks diam positif.',
                'expected_next_heat_date' => now()->subDays(49)->toDateString(),
                'notes' => 'Inseminasi ulang setelah hasil pemeriksaan negatif.',
                'created_by' => $manager->id,
            ]);

            $breeding->recordMating($negativeHeifer, $record->id, $manager);

            $check = PregnancyCheck::create([
                'animal_id' => $negativeHeifer->id,
                'breeding_record_id' => $record->id,
                'check_date' => now()->subDays(45)->toDateString(),
                'method' => PregnancyCheckMethod::Rectal,
                'result' => PregnancyResult::NotPregnant,
                'estimated_gestation_days' => 25,
                'expected_due_date' => null,
                'fetus_count' => 0,
                'notes' => 'Belum bunting; dijadwalkan inseminasi pada birahi berikutnya.',
                'veterinarian_id' => $vet->id,
                'created_by' => $manager->id,
            ]);

            $breeding->applyPregnancyCheck($check, $vet);
        }

        $naturalMatingCow = $lactating->first(
            fn (Animal $animal) => $animal->breeding_status === BreedingStatus::Open,
        );
        $bull = $bulls->first();

        if ($naturalMatingCow !== null && $bull !== null) {
            $record = BreedingRecord::create([
                'animal_id' => $naturalMatingCow->id,
                'record_type' => BreedingRecordType::NaturalMating,
                'breeding_date' => now()->subDays(16)->toDateString(),
                'bull_id' => $bull->id,
                'semen_code' => null,
                'semen_batch' => null,
                'inseminator' => 'Agus Prasetyo',
                'heat_signs' => 'Birahi pagi, sapi menerima pejantan.',
                'expected_next_heat_date' => now()->addDays(5)->toDateString(),
                'notes' => 'Perkawinan alami dengan pejantan kandang G.',
                'created_by' => $manager->id,
            ]);

            $breeding->recordMating($naturalMatingCow, $record->id, $manager);
        }

        $heatCow = $lactating
            ->filter(fn (Animal $animal) => $animal->breeding_status === BreedingStatus::Open)
            ->last();

        if ($heatCow !== null) {
            BreedingRecord::create([
                'animal_id' => $heatCow->id,
                'record_type' => BreedingRecordType::HeatObserved,
                'breeding_date' => now()->subDays(5)->toDateString(),
                'bull_id' => null,
                'semen_code' => null,
                'semen_batch' => null,
                'inseminator' => null,
                'heat_signs' => 'Birahi pagi, vulva merah dan lendir bening.',
                'expected_next_heat_date' => now()->addDays(16)->toDateString(),
                'notes' => 'Dicatat untuk perencanaan inseminasi berikutnya.',
                'created_by' => $manager->id,
            ]);
        }
    }

    /**
     * @param  Collection<int, Animal>  $calves
     * @param  Collection<int, Animal>  $bulls
     */
    private function birthHistory(Collection $calves, Collection $bulls, User $manager): void
    {
        $fallbackSire = $bulls->first();

        foreach ($calves->values() as $index => $calf) {
            $dam = $calf->dam;
            $sire = $calf->sire ?? $fallbackSire;

            if ($dam === null) {
                continue;
            }

            $birthDate = $calf->date_of_birth;
            $birthType = match ($index % 6) {
                4 => BirthType::Assisted,
                5 => BirthType::Difficult,
                default => BirthType::Normal,
            };
            $male = $calf->sex === Sex::Male;

            BirthRecord::create([
                'dam_id' => $dam->id,
                'sire_id' => $sire?->id,
                'animal_id' => $calf->id,
                'pregnancy_check_id' => null,
                'birth_date' => $birthDate->toDateString(),
                'birth_time' => '05:45',
                'birth_type' => $birthType,
                'birth_weight_kg' => $male ? 34 + ($index % 5) : 30 + ($index % 4),
                'colostrum_intake' => '3 liter dalam 2 jam pertama',
                'calf_sex' => $male ? 1 : 0,
                'assistance_notes' => $birthType === BirthType::Normal
                    ? 'Kelahiran normal tanpa bantuan.'
                    : 'Dibantu dua orang petugas kandang; pedet sehat.',
                'created_by' => $manager->id,
            ]);
        }
    }

    private function semenCode(?string $breedName): string
    {
        return match ($breedName) {
            'Holstein Friesian' => 'FH-07',
            'Jersey' => 'JRS-02',
            'Brown Swiss' => 'BSW-05',
            'Ayrshire' => 'AYR-04',
            'Sahiwal' => 'SAH-01',
            'Guernsey' => 'GNS-01',
            'Red Sindhi' => 'RSN-01',
            'Milking Shorthorn' => 'MSH-03',
            default => 'FH-07',
        };
    }
}
