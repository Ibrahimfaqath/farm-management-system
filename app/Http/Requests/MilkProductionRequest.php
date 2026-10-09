<?php

namespace App\Http\Requests;

use App\Enums\MilkSession;
use App\Enums\ProductionStatus;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\Barn;
use App\Models\MilkProduction;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MilkProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAbility('production.record') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'exists:farms,id'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'barn_id' => ['nullable', 'exists:barns,id'],
            'production_date' => ['required', 'date', 'before_or_equal:today'],
            'session' => ['required', Rule::enum(MilkSession::class)],
            'liters' => ['required', 'numeric', 'min:0', 'max:200'],
            'fat_percent' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'protein_percent' => ['nullable', 'numeric', 'min:0', 'max:15'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'farm_id' => 'peternakan',
            'animal_id' => 'ternak',
            'barn_id' => 'kandang',
            'production_date' => 'tanggal produksi',
            'fat_percent' => 'kadar lemak',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $animalId = $this->input('animal_id');
            $barnId = $this->input('barn_id');

            if (! $animalId && ! $barnId) {
                $validator->errors()->add('animal_id', 'Pilih salah satu: catatan per ekor atau catatan level kandang.');

                return;
            }

            if ($animalId && $barnId) {
                $validator->errors()->add('barn_id', 'Catatan hanya bisa untuk satu ekor ATAU level kandang, bukan keduanya.');
            }

            $animal = $animalId ? Animal::query()->find($animalId) : null;

            if ($animal && $animal->sex !== Sex::Female) {
                $validator->errors()->add('animal_id', 'Produksi susu hanya dapat dicatat untuk ternak betina.');
            }

            if ($animal && $animal->production_status !== ProductionStatus::Lactating && $animal->status->isOpen()) {
                $validator->errors()->add(
                    'animal_id',
                    'Status produksi hewan ini bukan Laktasi. Perbarui status bila hewan memang sudah laktasi.',
                );
            }

            if ($barnId && $animalId === null) {
                $barnBelongs = Barn::query()->whereKey($barnId)->where('farm_id', $this->input('farm_id'))->exists();

                if (! $barnBelongs) {
                    $validator->errors()->add('barn_id', 'Kandang bukan milik peternakan tersebut.');
                }
            }

            $this->avoidDuplicateEntry($validator);
        });
    }

    private function avoidDuplicateEntry(Validator $validator): void
    {
        $duplicate = MilkProduction::query()
            ->when($this->input('animal_id'), fn ($q) => $q->where('animal_id', $this->input('animal_id')))
            ->when(! $this->input('animal_id'), fn ($q) => $q->whereNull('animal_id')->where('barn_id', $this->input('barn_id')))
            ->whereDate('production_date', $this->input('production_date'))
            ->where('session', $this->input('session'))
            ->exists();

        if ($duplicate) {
            $validator->errors()->add('session', 'Sudah ada catatan untuk tanggal dan sesi yang sama. Gunakan kolom Catatan bila ingin menambah.');
        }
    }
}
