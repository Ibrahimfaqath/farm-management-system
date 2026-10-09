<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\PaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\FinanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['invoice', 'customer', 'recorder'])
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('payment_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->date('date_to')))
            ->latest('payment_date')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'customers' => Customer::orderBy('name')->pluck('name', 'id'),
            'filters' => $request->only(['search', 'customer_id', 'date_from', 'date_to']),
            'monthTotal' => (float) Payment::whereDate('payment_date', '>=', now()->startOfMonth())->sum('amount'),
        ]);
    }

    public function create(Request $request): View
    {
        $invoice = $request->integer('invoice_id')
            ? Invoice::query()->with('customer')->findOrFail($request->integer('invoice_id'))
            : null;

        return view('payments.create', [
            'payment' => new Payment(['payment_date' => now()->toDateString()]),
            'invoice' => $invoice,
            'customers' => Customer::orderBy('name')->get()
                ->mapWithKeys(fn ($c) => [$c->id => $c->name]),
            'openInvoices' => Invoice::query()
                ->outstanding()
                ->with('customer')
                ->orderBy('due_date')
                ->get()
                ->mapWithKeys(fn ($i) => [$i->id => $i->invoice_number.' · '.$i->customer->name.' (sisa Rp'.number_format($i->balance(), 0).')']),
            'methods' => $this->enumOptions(PaymentMethod::class),
        ]);
    }

    public function store(PaymentRequest $request, FinanceService $finance): RedirectResponse
    {
        $data = $request->validated();

        $result = $finance->recordPayment([
            'invoice_id' => $data['invoice_id'] ?? null,
            'customer_id' => $data['customer_id'],
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'method' => $data['method'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], $request->user());

        $message = 'Kas masuk Rp'.number_format($result['cash_in'], 0);

        if ($result['invoice']) {
            $invoice = $result['invoice'];
            $message .= '. Piutang faktur '.$invoice->invoice_number.': Rp'.number_format($result['ar_before'], 0)
                .' → Rp'.number_format($result['ar_after'], 0).' ('.$invoice->status->label().')';
        } else {
            $message .= ' (tanpa faktur)';
        }

        return redirect()
            ->route($result['invoice'] ? 'invoices.show' : 'payments.index', $result['invoice'] ?? null)
            ->with('status', $message.'.');
    }
}
