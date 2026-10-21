<?php

namespace App\Http\Controllers;

use App\Http\Requests\FarmRequest;
use App\Models\Farm;
use App\Services\AuditLogger;
use App\Services\CodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FarmController extends Controller
{
    public function index(Request $request): View
    {
        $farms = Farm::query()
            ->withCount(['barns', 'animals' => fn ($q) => $q->where('status', 'active')])
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q
                ->where(fn ($sub) => $sub->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('farms.index', ['farms' => $farms]);
    }

    public function create(CodeGenerator $codes): View
    {
        return view('farms.create', [
            'farm' => new Farm,
            'nextCode' => $codes->next('farms', 'code', 'FRM-'),
        ]);
    }

    public function store(FarmRequest $request, AuditLogger $audit, CodeGenerator $codes): RedirectResponse
    {
        $farm = DB::transaction(fn () => Farm::create($request->validated() + [
            'code' => $codes->next('farms', 'code', 'FRM-'),
        ]));

        $audit->log('farm.created', $farm, "Peternakan {$farm->name} dibuat", $farm->code);

        return redirect()->route('farms.show', $farm)->with('status', 'Peternakan berhasil dibuat.');
    }

    public function show(Farm $farm): View
    {
        $farm->load([
            'barns' => fn ($q) => $q->withCount(['animals' => fn ($sub) => $sub->where('status', 'active')]),
        ])->loadCount([
            'animals',
            'animals as active_animals_count' => fn ($q) => $q->where('status', 'active'),
        ]);

        return view('farms.show', ['farm' => $farm]);
    }

    public function edit(Farm $farm): View
    {
        return view('farms.edit', ['farm' => $farm]);
    }

    public function update(FarmRequest $request, Farm $farm, AuditLogger $audit): RedirectResponse
    {
        $farm->update($request->validated());

        $audit->log('farm.updated', $farm, "Data peternakan {$farm->name} diperbarui", $farm->code);

        return redirect()->route('farms.show', $farm)->with('status', 'Data peternakan diperbarui.');
    }
}
