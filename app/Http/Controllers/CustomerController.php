<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\AuditLogger;
use App\Services\CodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount(['invoices', 'salesOrders'])
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('code', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Customer $customer) => $customer->setAttribute('balance', $customer->outstandingBalance()));

        return view('customers.index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'type']),
            'types' => [
                'retail' => 'Ritel',
                'wholesale' => 'Grosir',
                'partner' => 'Mitra',
            ],
        ]);
    }

    public function create(CodeGenerator $codes): View
    {
        return view('customers.create', [
            'customer' => new Customer([
                'is_active' => true,
                'payment_terms_days' => 30,
            ]),
            'nextCode' => $codes->next('customers', 'code', 'CUS-'),
        ] + $this->formOptions());
    }

    public function store(CustomerRequest $request, AuditLogger $audit, CodeGenerator $codes): RedirectResponse
    {
        $customer = DB::transaction(fn () => Customer::create($request->validated() + [
            'code' => $codes->next('customers', 'code', 'CUS-'),
        ]));

        $audit->log('sales.customer_created', $customer, "Pelanggan {$customer->name} ditambahkan", $customer->code);

        return redirect()->route('customers.show', $customer)->with('status', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Customer $customer): View
    {
        $customer->loadCount(['invoices', 'salesOrders', 'payments']);

        return view('customers.show', [
            'customer' => $customer,
            'balance' => $customer->outstandingBalance(),
            'invoices' => $customer->invoices()->orderByDesc('invoice_date')->limit(15)->get(),
            'payments' => $customer->payments()->orderByDesc('payment_date')->limit(15)->get(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', ['customer' => $customer] + $this->formOptions());
    }

    public function update(CustomerRequest $request, Customer $customer, AuditLogger $audit): RedirectResponse
    {
        $customer->update($request->validated());

        $audit->log('sales.customer_updated', $customer, "Data pelanggan {$customer->name} diperbarui", $customer->code);

        return redirect()->route('customers.show', $customer)->with('status', 'Data pelanggan diperbarui.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'types' => [
                'retail' => 'Ritel',
                'wholesale' => 'Grosir',
                'partner' => 'Mitra',
            ],
            'termOptions' => [
                0 => 'Langsung (Bayar di tempat)',
                7 => 'Net 7',
                14 => 'Net 14',
                30 => 'Net 30',
                60 => 'Net 60',
            ],
        ];
    }
}
