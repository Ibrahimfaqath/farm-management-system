<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\CodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withCount(['purchaseOrders', 'goodsReceipts'])
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'category']),
            'categories' => [
                'feed' => 'Pakan',
                'equipment' => 'Peralatan',
                'other' => 'Lainnya',
                'mixed' => 'Campuran',
            ],
        ]);
    }

    public function create(CodeGenerator $codes): View
    {
        return view('suppliers.create', [
            'supplier' => new Supplier(['is_active' => true]),
            'nextCode' => $codes->next('suppliers', 'code', 'SUP-'),
        ] + $this->formOptions());
    }

    public function store(SupplierRequest $request, AuditLogger $audit, CodeGenerator $codes): RedirectResponse
    {
        $supplier = DB::transaction(fn () => Supplier::create($request->validated() + [
            'code' => $codes->next('suppliers', 'code', 'SUP-'),
        ]));

        $audit->log('supplier.created', $supplier, "Pemasok {$supplier->name} ditambahkan", $supplier->code);

        return redirect()->route('suppliers.index')->with('status', "Pemasok {$supplier->name} berhasil ditambahkan.");
    }

    public function show(Supplier $supplier): View
    {
        $supplier->loadCount(['purchaseOrders', 'goodsReceipts', 'payments']);

        return view('suppliers.show', [
            'supplier' => $supplier,
            'purchaseOrders' => $supplier->purchaseOrders()->withCount('items')->latest('order_date')->limit(10)->get(),
            'goodsReceipts' => $supplier->goodsReceipts()->latest('received_date')->limit(10)->get(),
            'outstanding' => $supplier->goodsReceipts()
                ->where('status', 'posted')
                ->get()
                ->sum(fn ($receipt) => $receipt->outstandingAmount()),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', ['supplier' => $supplier] + $this->formOptions());
    }

    public function update(SupplierRequest $request, Supplier $supplier, AuditLogger $audit): RedirectResponse
    {
        $supplier->update($request->validated());

        $audit->log('supplier.updated', $supplier, "Data pemasok {$supplier->name} diperbarui", $supplier->code);

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Data pemasok diperbarui.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'categories' => [
                'feed' => 'Pakan',
                'equipment' => 'Peralatan',
                'other' => 'Lainnya',
                'mixed' => 'Campuran',
            ],
        ];
    }
}
