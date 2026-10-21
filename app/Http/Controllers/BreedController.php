<?php

namespace App\Http\Controllers;

use App\Enums\AnimalSpecies;
use App\Http\Requests\BreedRequest;
use App\Models\Breed;
use App\Services\AuditLogger;
use App\Services\CodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BreedController extends Controller
{
    public function index(Request $request): View
    {
        $breeds = Breed::query()
            ->where('is_active', true)
            ->where('species', AnimalSpecies::Cattle->value)
            ->withCount('animals')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q
                ->where(fn ($sub) => $sub->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('breeds.index', ['breeds' => $breeds]);
    }

    public function create(CodeGenerator $codes): View
    {
        return view('breeds.create', [
            'breed' => new Breed(['species' => AnimalSpecies::Cattle->value, 'is_active' => true]),
            'nextCode' => $codes->next('breeds', 'code', 'BRD-'),
        ]);
    }

    public function store(BreedRequest $request, AuditLogger $audit, CodeGenerator $codes): RedirectResponse
    {
        $breed = DB::transaction(fn () => Breed::create($request->validated() + [
            'code' => $codes->next('breeds', 'code', 'BRD-'),
            'species' => AnimalSpecies::Cattle->value,
        ]));

        $audit->log('breed.created', $breed, "Ras {$breed->name} ditambahkan", $breed->code);

        return redirect()->route('breeds.index')->with('status', 'Ras berhasil ditambahkan.');
    }

    public function edit(Breed $breed): View
    {
        return view('breeds.edit', ['breed' => $breed]);
    }

    public function update(BreedRequest $request, Breed $breed, AuditLogger $audit): RedirectResponse
    {
        $breed->update($request->validated() + ['species' => AnimalSpecies::Cattle->value]);

        $audit->log('breed.updated', $breed, "Data ras {$breed->name} diperbarui", $breed->code);

        return redirect()->route('breeds.index')->with('status', 'Data ras diperbarui.');
    }
}
