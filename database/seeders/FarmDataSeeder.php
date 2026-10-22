<?php

namespace Database\Seeders;

use App\Enums\AcquisitionType;
use App\Enums\AnimalStatus;
use App\Enums\BreedingStatus;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\Breed;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Builds a coherent commercial dairy farm: breeds, barns, a herd with
 * genealogy, purchases and exits. The numbers are deliberately in the range a
 * small Indonesian dairy would actually run, not a handful of placeholder rows.
 */
class FarmDataSeeder extends Seeder
{
    /**
     * A repeating breed mix for the milking herd.
     *
     * Holstein Friesian dominates because that is what most Indonesian dairy
     * farms milk, with Jersey, Brown Swiss and the tropically adapted breeds
     * represented so the breed report has something real to compare.
     *
     * @var list<string>
     */
    private const BREED_CYCLE = [
        'BRD-HRF', 'BRD-HRF', 'BRD-JRS', 'BRD-HRF', 'BRD-BSW', 'BRD-HRF',
        'BRD-AYR', 'BRD-HRF', 'BRD-GNS', 'BRD-HRF', 'BRD-MSH', 'BRD-HRF',
        'BRD-SAH', 'BRD-HRF', 'BRD-RSN', 'BRD-HRF',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $farm = Farm::create([
                'code' => 'FRM-001',
                'name' => 'Peternakan Suka Maju',
                'address' => 'Jl. Raya Pujon No. 88',
                'village' => 'Pujon Kidul',
                'district' => 'Pujon',
                'regency' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65391',
                'phone' => '081234567890',
                'manager_name' => 'Budi Santoso',
                'area_hectares' => 8.5,
                'established_date' => '2016-05-10',
                'notes' => 'Peternakan sapi perah rakyat yang memasok susu segar ke koperasi dan pengepul di Malang.',
            ]);

            $breeds = $this->breeds();
            $barns = $this->barns($farm);
            $users = $this->users($farm);

            $animals = $this->herd($farm, $breeds, $barns);

            $this->linkUsersToFarm($users);

            $this->command?->info('Peternakan, '.count($breeds).' ras, '.count($barns).' kandang, dan '.count($animals).' ekor ternak dibuat.');
        });
    }

    /** @return array<string, Breed> */
    private function breeds(): array
    {
        $definitions = [
            ['BRD-HRF', 'Holstein Friesian', 'cattle', 'Netherlands', 22.5, 305, 600, 'Ras perah produksi tinggi; perlu pengelolaan panas dan pakan yang baik.'],
            ['BRD-JRS', 'Jersey', 'cattle', 'Jersey Island', 14.0, 290, 420, 'Produksi lebih rendah tetapi kadar lemak dan protein susu tinggi.'],
            ['BRD-BSW', 'Brown Swiss', 'cattle', 'Switzerland', 16.0, 300, 600, 'Tahan cuaca dingin dan dataran tinggi; produksi susu sedang.'],
            ['BRD-AYR', 'Ayrshire', 'cattle', 'Scotland', 15.0, 295, 500, 'Adaptif, mudah dipelihara, dan produksi susu stabil.'],
            ['BRD-SAH', 'Sahiwal', 'cattle', 'Pakistan', 8.0, 280, 400, 'Sapi perah tropis yang tahan panas dan penyakit.'],
            ['BRD-GNS', 'Guernsey', 'cattle', 'Guernsey', 13.5, 290, 450, 'Susu berwarna lebih kuning dengan kadar lemak tinggi.'],
            ['BRD-RSN', 'Red Sindhi', 'cattle', 'Pakistan', 7.5, 275, 350, 'Tahan panas, produksi rendah, sering digunakan untuk persilangan.'],
            ['BRD-MSH', 'Milking Shorthorn', 'cattle', 'United Kingdom', 14.0, 290, 550, 'Tipe perah dengan temperamen tenang dan produksi sedang.'],
        ];

        $breeds = [];

        foreach ($definitions as [$code, $name, $species, $origin, $milk, $lactation, $weight, $notes]) {
            $breeds[$code] = Breed::create([
                'code' => $code,
                'name' => $name,
                'species' => $species,
                'origin' => $origin,
                'average_milk_liters' => $milk,
                'average_lactation_days' => $lactation,
                'adult_weight_kg' => $weight,
                'is_active' => true,
                'notes' => $notes,
            ]);
        }

        return $breeds;
    }

    /** @return array<string, Barn> */
    private function barns(Farm $farm): array
    {
        $definitions = [
            ['KAND-A', 'Kandang Laktasi A', 'dairy', 40, 480, 'Kandang terbuka dengan tempat pakan dan air minum otomatis.'],
            ['KAND-B', 'Kandang Laktasi B', 'dairy', 40, 450, 'Kandang laktasi untuk sapi periode produksi berikutnya.'],
            ['KAND-C', 'Kandang Kering', 'dry', 20, 240, 'Kandang sapi masa kering dan sapi yang menunggu kelahiran.'],
            ['KAND-D', 'Kandang Pedet', 'calf', 30, 220, 'Kandang pedet dengan tempat susu dan bedding jerami.'],
            ['KAND-E', 'Kandang Dara', 'breeding', 25, 260, 'Kandang dara dan calon induk sebelum masuk kandang laktasi.'],
            ['KAND-F', 'Kandang Karantina', 'quarantine', 8, 80, 'Kandang isolasi untuk ternak baru atau ternak sakit menular.'],
            ['KAND-G', 'Kandang Pejantan', 'breeding', 5, 90, 'Kandang pejantan dengan lapangan latihan terbatas.'],
        ];

        $barns = [];

        foreach ($definitions as [$code, $name, $type, $capacity, $area, $notes]) {
            $barns[$code] = Barn::create([
                'farm_id' => $farm->id,
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'capacity' => $capacity,
                'area_m2' => $area,
                'is_active' => true,
                'notes' => $notes,
            ]);
        }

        return $barns;
    }

    /** @return array<string, User> */
    private function users(Farm $farm): array
    {
        $definitions = [
            ['owner', 'Hendra Wijaya', 'owner@farm.test', '081200000001'],
            ['manager', 'Siti Rahayu', 'manager@farm.test', '081200000002'],
            ['worker', 'Agus Prasetyo', 'worker@farm.test', '081200000003'],
            ['worker2', 'Dedi Kurniawan', 'worker2@farm.test', '081200000004'],
            ['veterinarian', 'dr. Rina Kusuma', 'vet@farm.test', '081200000005'],
            ['finance', 'Maya Puspita', 'finance@farm.test', '081200000006'],
        ];

        $users = [];

        foreach ($definitions as [$key, $name, $email, $phone]) {
            $users[$key] = User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'role' => $key === 'worker2' ? 'worker' : $key,
                'phone' => $phone,
                'farm_id' => $farm->id,
                'email_verified_at' => now(),
            ]);
        }

        return $users;
    }

    /** @param array<string, User> $users */
    private function linkUsersToFarm(array $users): void
    {
        // Owner and finance stay farm-agnostic so they can see the whole operation.
        $users['owner']->update(['farm_id' => null]);
        $users['finance']->update(['farm_id' => null]);
    }

    /**
     * @param  array<string, Breed>  $breeds
     * @param  array<string, Barn>  $barns
     * @return array<string, Animal>
     */
    private function herd(Farm $farm, array $breeds, array $barns): array
    {
        $animals = [];

        $create = function (array $attributes) use ($farm, &$animals): Animal {
            $animal = Animal::create($attributes + [
                'farm_id' => $farm->id,
                'status' => AnimalStatus::Active,
                'acquisition_type' => AcquisitionType::Birth,
                'acquisition_cost' => 0,
                'created_by' => null,
            ]);

            $animals[$animal->animal_code] = $animal;

            return $animal;
        };

        // Two breeding bulls. They are the sires used by the natural-mating
        // record; most of the herd is bred by artificial insemination.
        $bulls = [];

        foreach ([
            ['BL-001', 'BRD-HRF', 'Jaka', 5, 28_000_000],
            ['BL-002', 'BRD-BSW', 'Satria', 4, 24_000_000],
        ] as $index => [$code, $breedCode, $name, $age, $cost]) {
            $bulls[] = $create([
                'animal_code' => $code,
                'name' => $name,
                'breed_id' => $breeds[$breedCode]->id,
                'barn_id' => $barns['KAND-G']->id,
                'current_barn_id' => $barns['KAND-G']->id,
                'sex' => Sex::Male,
                'production_status' => ProductionStatus::Bull,
                'breeding_status' => BreedingStatus::NotApplicable,
                'date_of_birth' => now()->subYears($age)->subMonths($index * 3)->toDateString(),
                'acquisition_date' => now()->subYears(3)->subMonths($index * 4)->toDateString(),
                'acquisition_type' => AcquisitionType::Purchase,
                'acquisition_cost' => $cost,
                'source_name' => 'CV Ternak Sumber Makmur',
                'notes' => 'Pejantan untuk pelayanan inseminasi alami dan cadangan genetik.',
            ]);
        }

        $lactatingA = [];

        for ($i = 1; $i <= 20; $i++) {
            $breedCode = self::BREED_CYCLE[($i - 1) % count(self::BREED_CYCLE)];
            $birthDate = now()->subYears(3 + ($i % 4))->subMonths($i % 8)->toDateString();
            $purchased = in_array($i, [7, 14, 19], true);

            $lactatingA[] = $create([
                'animal_code' => sprintf('LF-A%03d', $i),
                'breed_id' => $breeds[$breedCode]->id,
                'barn_id' => $barns['KAND-A']->id,
                'current_barn_id' => $barns['KAND-A']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Lactating,
                'breeding_status' => BreedingStatus::Open,
                'date_of_birth' => $birthDate,
                'acquisition_date' => $purchased ? now()->subYears(2)->subMonths($i)->toDateString() : $birthDate,
                'acquisition_type' => $purchased ? AcquisitionType::Purchase : AcquisitionType::Birth,
                'acquisition_cost' => $purchased ? 19_500_000 + ($i * 250_000) : 0,
                'source_name' => $purchased ? 'CV Ternak Sumber Makmur' : null,
                'notes' => $purchased
                    ? 'Dibeli sebagai sapi laktasi produktif dari peternakan mitra.'
                    : 'Lahir dan dibesarkan di peternakan.',
            ]);
        }

        $lactatingB = [];

        for ($i = 1; $i <= 16; $i++) {
            $breedCode = self::BREED_CYCLE[($i + 5) % count(self::BREED_CYCLE)];
            $birthDate = now()->subYears(3 + ($i % 5))->subMonths($i % 9)->toDateString();
            $purchased = in_array($i, [4, 11], true);

            $lactatingB[] = $create([
                'animal_code' => sprintf('LF-B%03d', $i),
                'breed_id' => $breeds[$breedCode]->id,
                'barn_id' => $barns['KAND-B']->id,
                'current_barn_id' => $barns['KAND-B']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Lactating,
                'breeding_status' => BreedingStatus::Open,
                'date_of_birth' => $birthDate,
                'acquisition_date' => $purchased ? now()->subYears(2)->subMonths($i + 2)->toDateString() : $birthDate,
                'acquisition_type' => $purchased ? AcquisitionType::Purchase : AcquisitionType::Birth,
                'acquisition_cost' => $purchased ? 20_000_000 + ($i * 300_000) : 0,
                'source_name' => $purchased ? 'CV Ternak Sumber Makmur' : null,
                'notes' => $purchased
                    ? 'Dibeli untuk menambah kapasitas produksi kandang B.'
                    : 'Lahir dan dibesarkan di peternakan.',
            ]);
        }

        // Four cows already confirmed pregnant while still lactating. They show
        // the independent production/breeding status facets working together.
        $pregnantDefinitions = [
            ['PR-001', 'BRD-JRS', 'KAND-A'],
            ['PR-002', 'BRD-BSW', 'KAND-B'],
            ['PR-003', 'BRD-HRF', 'KAND-A'],
            ['PR-004', 'BRD-GNS', 'KAND-B'],
        ];

        foreach ($pregnantDefinitions as $index => [$code, $breedCode, $barnCode]) {
            $create([
                'animal_code' => $code,
                'breed_id' => $breeds[$breedCode]->id,
                'barn_id' => $barns[$barnCode]->id,
                'current_barn_id' => $barns[$barnCode]->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Lactating,
                'breeding_status' => BreedingStatus::ConfirmedPregnant,
                'date_of_birth' => now()->subYears(4 + $index)->subMonths($index + 2)->toDateString(),
                'acquisition_date' => now()->subYears(3)->subMonths($index + 1)->toDateString(),
                'acquisition_type' => AcquisitionType::Birth,
                'notes' => 'Laktasi berjalan dengan pemeriksaan kebuntingan terkonfirmasi.',
            ]);
        }

        // Dry cows, two of them pregnant and waiting for calving.
        for ($i = 1; $i <= 6; $i++) {
            $breedCode = ['BRD-JRS', 'BRD-HRF', 'BRD-BSW', 'BRD-AYR', 'BRD-GNS', 'BRD-HRF'][$i - 1];
            $pregnant = $i <= 2;

            $create([
                'animal_code' => sprintf('DR-%03d', $i),
                'breed_id' => $breeds[$breedCode]->id,
                'barn_id' => $barns['KAND-C']->id,
                'current_barn_id' => $barns['KAND-C']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Dry,
                'breeding_status' => $pregnant ? BreedingStatus::ConfirmedPregnant : BreedingStatus::Open,
                'date_of_birth' => now()->subYears(4 + ($i % 4))->subMonths($i * 2)->toDateString(),
                'acquisition_date' => now()->subYears(3)->subMonths($i)->toDateString(),
                'acquisition_type' => AcquisitionType::Birth,
                'notes' => $pregnant
                    ? 'Masa kering, menunggu kelahiran pada siklus berikutnya.'
                    : 'Masa kering setelah laktasi selesai.',
            ]);
        }

        // Twelve heifers being raised as replacement stock.
        for ($i = 1; $i <= 12; $i++) {
            $breedCode = self::BREED_CYCLE[($i + 9) % count(self::BREED_CYCLE)];
            $dam = $lactatingA[($i - 1) % count($lactatingA)];

            $create([
                'animal_code' => sprintf('HF-%03d', $i),
                'breed_id' => $breeds[$breedCode]->id,
                'barn_id' => $barns['KAND-E']->id,
                'current_barn_id' => $barns['KAND-E']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Heifer,
                'breeding_status' => BreedingStatus::Open,
                'date_of_birth' => now()->subMonths(18 + ($i * 2))->toDateString(),
                'acquisition_date' => now()->subMonths(18 + ($i * 2))->toDateString(),
                'acquisition_type' => AcquisitionType::Birth,
                'dam_id' => $dam->id,
                'sire_id' => $bulls[$i % 2]->id,
                'notes' => 'Dara pengganti, dipantau pertumbuhannya setiap bulan.',
            ]);
        }

        // Ten calves, all with recorded dams and sires.
        for ($i = 1; $i <= 10; $i++) {
            $dam = $lactatingA[($i - 1) % count($lactatingA)];
            $male = $i % 3 === 0;
            $birthDate = now()->subDays(20 + ($i * 18))->toDateString();

            $create([
                'animal_code' => sprintf('CF-%03d', $i),
                'breed_id' => $dam->breed_id,
                'barn_id' => $barns['KAND-D']->id,
                'current_barn_id' => $barns['KAND-D']->id,
                'sex' => $male ? Sex::Male : Sex::Female,
                'production_status' => ProductionStatus::Calf,
                'breeding_status' => $male ? BreedingStatus::NotApplicable : BreedingStatus::Open,
                'date_of_birth' => $birthDate,
                'acquisition_date' => $birthDate,
                'acquisition_type' => AcquisitionType::Birth,
                'dam_id' => $dam->id,
                'sire_id' => $bulls[$i % 2]->id,
                'notes' => $male
                    ? 'Pedet jantan, dipelihara untuk penggemukan atau dijual.'
                    : 'Pedet betina, calon induk pengganti.',
            ]);
        }

        // Exited animals stay in the dataset so history and audit trails remain
        // explainable. Their milk records are placed before the exit date.
        $create([
            'animal_code' => 'LF-C001',
            'breed_id' => $breeds['BRD-HRF']->id,
            'barn_id' => $barns['KAND-A']->id,
            'current_barn_id' => $barns['KAND-A']->id,
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Lactating,
            'breeding_status' => BreedingStatus::Open,
            'date_of_birth' => now()->subYears(6)->subMonths(2)->toDateString(),
            'acquisition_date' => now()->subYears(4)->toDateString(),
            'acquisition_type' => AcquisitionType::Birth,
            'status' => AnimalStatus::Sold,
            'exit_date' => now()->subDays(75)->toDateString(),
            'exit_type' => AnimalStatus::Sold->value,
            'exit_value' => 18_500_000,
            'exit_reference' => 'NOTA-2401',
            'exit_notes' => 'Dijual ke pembeli dari Surabaya. Alasan: harga tawaran baik dan pembeli lama.',
        ]);

        $create([
            'animal_code' => 'LF-C002',
            'breed_id' => $breeds['BRD-SAH']->id,
            'barn_id' => $barns['KAND-B']->id,
            'current_barn_id' => $barns['KAND-B']->id,
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Lactating,
            'breeding_status' => BreedingStatus::Open,
            'date_of_birth' => now()->subYears(7)->subMonths(1)->toDateString(),
            'acquisition_date' => now()->subYears(5)->toDateString(),
            'acquisition_type' => AcquisitionType::Birth,
            'status' => AnimalStatus::Deceased,
            'exit_date' => now()->subDays(40)->toDateString(),
            'exit_type' => AnimalStatus::Deceased->value,
            'exit_notes' => 'Mati setelah sakit selama empat hari. Diperiksa dokter hewan; riwayat tetap tersimpan.',
        ]);

        $create([
            'animal_code' => 'LF-C003',
            'breed_id' => $breeds['BRD-JRS']->id,
            'barn_id' => $barns['KAND-B']->id,
            'current_barn_id' => $barns['KAND-B']->id,
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Lactating,
            'breeding_status' => BreedingStatus::Open,
            'date_of_birth' => now()->subYears(5)->subMonths(4)->toDateString(),
            'acquisition_date' => now()->subYears(3)->toDateString(),
            'acquisition_type' => AcquisitionType::Birth,
            'status' => AnimalStatus::Transferred,
            'exit_date' => now()->subDays(120)->toDateString(),
            'exit_type' => AnimalStatus::Transferred->value,
            'exit_value' => 15_500_000,
            'exit_reference' => 'BAST-2601-03',
            'exit_notes' => 'Dipindahkan ke peternakan mitra di Blitar untuk program persilangan.',
        ]);

        $this->nameTheHerd($animals);

        return $animals;
    }

    /**
     * Give every animal a name.
     *
     * A working dairy names its cattle — the name is how the keeper refers to an
     * animal across the yard, the milk book and the treatment record. Leaving
     * the column blank made the livestock list read like unfinished data, and a
     * column of dashes down an entire index page is not something a reviewer
     * should have to see.
     *
     * Names are drawn in code order rather than at random, so re-seeding
     * produces the same herd: a demo that reshuffles itself on every run cannot
     * be talked about, screenshotted or compared. Animals that already carry a
     * name are left alone.
     *
     * @param  array<string, Animal>  $animals
     */
    private function nameTheHerd(array $animals): void
    {
        $femaleNames = [
            'Sari', 'Maya', 'Indah', 'Lestari', 'Ayu', 'Putri', 'Melati', 'Mawar',
            'Kenanga', 'Cendana', 'Anggrek', 'Dahlia', 'Melur', 'Flamboyan', 'Pala', 'Jambu',
            'Salak', 'Rambutan', 'Markisa', 'Sirsak', 'Belimbing', 'Sawo', 'Mangga', 'Jeruk',
            'Kamboja', 'Cempaka', 'Teratai', 'Lotus', 'Krisna', 'Sekar', 'Wulandari', 'Retno',
            'Endah', 'Nirmala', 'Sasmi', 'Wulan', 'Sri', 'Dewi', 'Ratna', 'Intan',
            'Permata', 'Rani', 'Ningsih', 'Yuni', 'Tuti', 'Siti', 'Nur', 'Ani',
            'Diah', 'Rini', 'Wati', 'Ika', 'Lina', 'Fitri', 'Yanti', 'Sukma',
            'Bunga', 'Cahaya', 'Mentari', 'Bulan', 'Bintang', 'Awan', 'Embun', 'Gita',
            'Rasa', 'Karsih', 'Legi', 'Manis', 'Asih', 'Sayang', 'Kasih', 'Puspita',
        ];

        $maleNames = [
            'Jaka', 'Satria', 'Panji', 'Surya', 'Bayu', 'Bimo',
            'Rajawali', 'Elang', 'Merak', 'Kancil', 'Kijang', 'Cendrawasih',
            'Banteng', 'Garuda', 'Arjuna', 'Wira',
        ];

        $next = ['female' => 0, 'male' => 0];
        $pools = ['female' => $femaleNames, 'male' => $maleNames];
        $taken = [];

        foreach (collect($animals)->sortBy(fn (Animal $animal) => $animal->animal_code) as $animal) {
            if ($animal->name !== null && $animal->name !== '') {
                $taken[$animal->name] = ($taken[$animal->name] ?? 0) + 1;

                continue;
            }

            $sex = $animal->sex === Sex::Male ? 'male' : 'female';
            $pool = $pools[$sex];
            $start = $next[$sex];
            $name = null;

            // Skip names already used by an explicitly named animal (the two
            // bulls), then fall back to a numbered name only if the pool is
            // genuinely exhausted.
            for ($attempt = 0; $attempt < count($pool); $attempt++) {
                $candidate = $pool[($start + $attempt) % count($pool)];

                if (! isset($taken[$candidate])) {
                    $name = $candidate;
                    $next[$sex] = $start + $attempt + 1;

                    break;
                }
            }

            if ($name === null) {
                $base = $pool[$start % count($pool)];
                $suffix = 2;

                while (isset($taken[$base.' '.$suffix])) {
                    $suffix++;
                }

                $name = $base.' '.$suffix;
                $next[$sex] = $start + 1;
            }

            $animal->forceFill(['name' => $name])->save();
            $taken[$name] = 1;
        }
    }
}
