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
 * Builds a coherent farm: breeds, barns, a herd with genealogy and exits.
 */
class FarmDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $farm = Farm::create([
                'code' => 'FRM-001',
                'name' => 'Peternakan Suka Maju',
                'address' => 'Jl. Raya Sukamaju No. 88',
                'village' => 'Sukamaju',
                'district' => 'Gondang',
                'regency' => 'Malang',
                'province' => 'Jawa Timur',
                'postal_code' => '65174',
                'phone' => '081234567890',
                'manager_name' => 'Budi Santoso',
                'area_hectares' => 12.5,
                'established_date' => '2016-05-10',
                'notes' => 'Peternakan sapi perah fokus pada produksi susu segar untuk pasar Jawa Timur.',
            ]);

            $breeds = $this->breeds();
            $barns = $this->barns($farm);
            $users = $this->users($farm);

            $animals = $this->herd($farm, $breeds, $barns);

            $this->linkUsersToFarm($users, $farm);

            $this->command?->info('Peternakan, ras, kandang, pengguna, dan 40 ekor hewan dibuat.');
        });
    }

    /** @return array<string, Breed> */
    private function breeds(): array
    {
        $definitions = [
            ['BRD-HRF', 'Holstein Friesian', 'cattle', 'Denmark', 22.5, 305, 600],
            ['BRD-JRS', 'Jersey', 'cattle', 'UK', 14.0, 290, 420],
            ['BRD-BSW', 'Brown Swiss', 'cattle', 'Switzerland', 16.0, 300, 600],
            ['BRD-AYR', 'Ayrshire', 'cattle', 'Scotland', 15.0, 295, 500],
            ['BRD-SAH', 'Sahiwal', 'cattle', 'Pakistan', 8.0, 280, 400],
        ];

        $breeds = [];

        foreach ($definitions as [$code, $name, $species, $origin, $milk, $lactation, $weight]) {
            $breeds[$code] = Breed::create([
                'code' => $code,
                'name' => $name,
                'species' => $species,
                'origin' => $origin,
                'average_milk_liters' => $milk,
                'average_lactation_days' => $lactation,
                'adult_weight_kg' => $weight,
                'is_active' => true,
            ]);
        }

        return $breeds;
    }

    /** @return array<string, Barn> */
    private function barns(Farm $farm): array
    {
        $definitions = [
            ['KAND-A', 'Kandang Laktasi A', 'dairy', 40, 320],
            ['KAND-B', 'Kandang Laktasi B', 'dairy', 40, 320],
            ['KAND-C', 'Kandang Kering', 'dry', 20, 180],
            ['KAND-D', 'Kandang Anakan', 'calf', 30, 200],
            ['KAND-E', 'Kandang Karantina', 'quarantine', 8, 70],
        ];

        $barns = [];

        foreach ($definitions as [$code, $name, $type, $capacity, $area]) {
            $barns[$code] = Barn::create([
                'farm_id' => $farm->id,
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'capacity' => $capacity,
                'area_m2' => $area,
                'is_active' => true,
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
    private function linkUsersToFarm(array $users, Farm $farm): void
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
        $sequence = 1;

        $create = function (array $attributes) use ($farm, &$animals, &$sequence): Animal {
            $animal = Animal::create($attributes + [
                'farm_id' => $farm->id,
                'status' => AnimalStatus::Active,
                'acquisition_type' => AcquisitionType::Birth,
                'acquisition_cost' => 0,
                'created_by' => null,
            ]);

            $animals[$animal->animal_code] = $animal;
            $sequence++;

            return $animal;
        };

        $lactatingA = [];
        $lactatingB = [];

        // 14 lactating cows in barn A
        foreach (range(1, 14) as $i) {
            $animal = $create([
                'animal_code' => sprintf('LF-A%03d', $i),
                'name' => $i <= 6 ? ['Sari', 'Maya', 'Indah', 'Lestari', 'Ayu', 'Putri'][$i - 1] : null,
                'breed_id' => $i % 3 === 0 ? $breeds['BRD-JRS']->id : $breeds['BRD-HRF']->id,
                'barn_id' => $barns['KAND-A']->id,
                'current_barn_id' => $barns['KAND-A']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Lactating,
                'breeding_status' => BreedingStatus::Open,
                'date_of_birth' => now()->subYears(random_int(3, 7))->subMonths(random_int(0, 11))->toDateString(),
            ]);
            $lactatingA[] = $animal;
        }

        // 10 lactating cows in barn B
        foreach (range(1, 10) as $i) {
            $animal = $create([
                'animal_code' => sprintf('LF-B%03d', $i),
                'breed_id' => $i % 4 === 0 ? $breeds['BRD-BSW']->id : $breeds['BRD-HRF']->id,
                'barn_id' => $barns['KAND-B']->id,
                'current_barn_id' => $barns['KAND-B']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Lactating,
                'breeding_status' => BreedingStatus::Open,
                'date_of_birth' => now()->subYears(random_int(3, 6))->subMonths(random_int(0, 8))->toDateString(),
            ]);
            $lactatingB[] = $animal;
        }

        // 3 dry cows
        foreach (range(1, 3) as $i) {
            $create([
                'animal_code' => sprintf('DR-%03d', $i),
                'breed_id' => $breeds['BRD-HRF']->id,
                'barn_id' => $barns['KAND-C']->id,
                'current_barn_id' => $barns['KAND-C']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Dry,
                'breeding_status' => BreedingStatus::Open,
                'date_of_birth' => now()->subYears(random_int(4, 6))->toDateString(),
            ]);
        }

        // 2 confirmed pregnant
        foreach (range(1, 2) as $i) {
            $create([
                'animal_code' => sprintf('PR-%03d', $i),
                'breed_id' => $breeds['BRD-JRS']->id,
                'barn_id' => $barns['KAND-C']->id,
                'current_barn_id' => $barns['KAND-C']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Lactating,
                'breeding_status' => BreedingStatus::ConfirmedPregnant,
                'date_of_birth' => now()->subYears(random_int(4, 6))->toDateString(),
            ]);
        }

        // 8 heifers (breeding candidates)
        foreach (range(1, 8) as $i) {
            $create([
                'animal_code' => sprintf('HF-%03d', $i),
                'breed_id' => $i % 2 === 0 ? $breeds['BRD-BSW']->id : $breeds['BRD-HRF']->id,
                'barn_id' => $barns['KAND-B']->id,
                'current_barn_id' => $barns['KAND-B']->id,
                'sex' => Sex::Female,
                'production_status' => ProductionStatus::Heifer,
                'breeding_status' => BreedingStatus::Open,
                'date_of_birth' => now()->subYears(2)->subMonths(random_int(0, 11))->toDateString(),
            ]);
        }

        // 6 calves with parents
        foreach (range(1, 6) as $i) {
            $dam = $lactatingA[($i - 1) % count($lactatingA)];

            $create([
                'animal_code' => sprintf('CF-%03d', $i),
                'breed_id' => $dam->breed_id,
                'barn_id' => $barns['KAND-D']->id,
                'current_barn_id' => $barns['KAND-D']->id,
                'sex' => $i % 3 === 0 ? Sex::Male : Sex::Female,
                'production_status' => ProductionStatus::Calf,
                'breeding_status' => $i % 3 === 0 ? BreedingStatus::NotApplicable : BreedingStatus::Open,
                'date_of_birth' => now()->subDays(random_int(30, 180))->toDateString(),
                'dam_id' => $dam->id,
            ]);
        }

        // 2 breeding bulls
        foreach (range(1, 2) as $i) {
            $create([
                'animal_code' => sprintf('BL-%03d', $i),
                'breed_id' => $i === 1 ? $breeds['BRD-HRF']->id : $breeds['BRD-BSW']->id,
                'barn_id' => $barns['KAND-B']->id,
                'current_barn_id' => $barns['KAND-B']->id,
                'sex' => Sex::Male,
                'production_status' => ProductionStatus::Bull,
                'breeding_status' => BreedingStatus::NotApplicable,
                'date_of_birth' => now()->subYears(random_int(3, 5))->toDateString(),
                'acquisition_cost' => random_int(15_000_000, 30_000_000),
                'acquisition_type' => AcquisitionType::Purchase,
                'source_name' => 'CV Tani Makmur',
            ]);
        }

        // 2 animals already out of the herd, history preserved
        $sold = $create([
            'animal_code' => 'LF-C001',
            'breed_id' => $breeds['BRD-HRF']->id,
            'barn_id' => $barns['KAND-A']->id,
            'current_barn_id' => $barns['KAND-A']->id,
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Lactating,
            'date_of_birth' => now()->subYears(5)->toDateString(),
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
            'barn_id' => $barns['KAND-A']->id,
            'current_barn_id' => $barns['KAND-A']->id,
            'sex' => Sex::Female,
            'production_status' => ProductionStatus::Lactating,
            'date_of_birth' => now()->subYears(6)->toDateString(),
            'status' => AnimalStatus::Deceased,
            'exit_date' => now()->subDays(40)->toDateString(),
            'exit_type' => AnimalStatus::Deceased->value,
            'exit_notes' => 'Mati setelah sakit selama empat hari. Alasan: diperiksa dokter hewan, riwayat tetap tersimpan.',
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
            'Melati', 'Mawar', 'Kenanga', 'Cendana', 'Anggrek', 'Dahlia', 'Melur', 'Flamboyan',
            'Pala', 'Jambu', 'Salak', 'Rambutan', 'Markisa', 'Sirsak', 'Belimbing', 'Sawo',
            'Mangga', 'Jeruk', 'Kamboja', 'Cempaka', 'Teratai', 'Lotus', 'Krisna', 'Sekar',
            'Wulandari', 'Retno', 'Endah', 'Nirmala', 'Sasmi', 'Wulan',
        ];

        $maleNames = [
            'Jaka', 'Satria', 'Panji', 'Surya', 'Bayu', 'Bimo',
            'Rajawali', 'Elang', 'Merak', 'Kancil', 'Kijang', 'Cendrawasih',
        ];

        $next = ['female' => 0, 'male' => 0];
        $pools = ['female' => $femaleNames, 'male' => $maleNames];
        $taken = [];

        foreach (collect($animals)->sortBy(fn (Animal $a) => $a->animal_code) as $animal) {
            if ($animal->name !== null && $animal->name !== '') {
                $taken[$animal->name] = ($taken[$animal->name] ?? 0) + 1;

                continue;
            }

            $sex = $animal->sex === Sex::Male ? 'male' : 'female';
            $pool = $pools[$sex];
            $name = $pool[$next[$sex] % count($pool)];

            // A yard does not run two animals under one name, so a repeat gets
            // a number — which is what a keeper actually calls the second one.
            while (isset($taken[$name])) {
                $name = $pool[$next[$sex] % count($pool)].' '.($taken[$pool[$next[$sex] % count($pool)]] + 1);
                $next[$sex]++;
            }

            $animal->forceFill(['name' => $name])->save();
            $taken[$name] = 1;
            $next[$sex]++;
        }
    }
}
