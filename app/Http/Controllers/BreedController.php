<?php

namespace App\Http\Controllers;

use App\Enums\AnimalSpecies;
use App\Http\Requests\BreedRequest;
use App\Models\Breed;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BreedController extends Controller
{
    public function index(Request $request): View
    {
        $breeds = Breed::query()
            ->withCount('animals')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q
                ->where(fn ($sub) => $sub->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('species'), fn ($q) => $q->where('species', $request->string('species')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('breeds.index', [
            'breeds' => $breeds,
            'speciesOptions' => $this->enumOptions(AnimalSpecies::class),
        ]);
    }

    public function create(): View
    {
        return view('breeds.create', ['breed' => new Breed(['species' => 'cattle', 'is_active' => true])] + $this->formOptions());
    }

    public function store(BreedRequest $request, AuditLogger $audit): RedirectResponse
    {
        $breed = Breed::create($request->validated());

        $audit->log('breed.created', $breed, "Ras {$breed->name} ditambahkan", $breed->code);

        return redirect()->route('breeds.index')->with('status', 'Ras berhasil ditambahkan.');
    }

    public function edit(Breed $breed): View
    {
        return view('breeds.edit', ['breed' => $breed] + $this->formOptions());
    }

    public function update(BreedRequest $request, Breed $breed, AuditLogger $audit): RedirectResponse
    {
        $breed->update($request->validated());

        $audit->log('breed.updated', $breed, "Data ras {$breed->name} diperbarui", $breed->code);

        return redirect()->route('breeds.index')->with('status', 'Data ras diperbarui.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return ['speciesOptions' => $this->enumOptions(AnimalSpecies::class)];
    }
}
