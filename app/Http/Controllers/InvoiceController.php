<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\AuditLogger;
use App\Services\FinanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request, FinanceService $finance): View
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->withSum('payments', 'amount')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('invoice_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('overdue'), fn ($q) => $q->overdue())
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->latest('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'overdue', 'customer_id']),
            'statuses' => $this->enumOptions(InvoiceStatus::class),
            'customers' => Customer::orderBy('name')->pluck('name', 'id'),
            'receivable' => $finance->accountsReceivable(),
            'overdueAmount' => $finance->overdueReceivable(),
            'overdueCount' => Invoice::query()->overdue()->count(),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'salesOrder', 'payments.recorder', 'creator']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'balance' => $invoice->balance(),
            'paidAmount' => (float) $invoice->payments()->sum('amount'),
            'cashTransactions' => $invoice->payments()
                ->with(['cashTransaction'])
                ->get(),
        ]);
    }

    public function document(Invoice $invoice): View
    {
        $invoice->load(['customer', 'salesOrder.items', 'creator']);

        return view('invoices.document', ['invoice' => $invoice]);
    }

    public function cancel(Invoice $invoice, AuditLogger $audit): RedirectResponse
    {
        abort_if($invoice->payments()->exists(), 403, 'Faktur yang sudah menerima pembayaran tidak dapat dibatalkan.');
        abort_if($invoice->status === InvoiceStatus::Cancelled, 403, 'Faktur ini sudah dibatalkan.');

        $invoice->update([
            'status' => InvoiceStatus::Cancelled,
            'notes' => trim(($invoice->notes ?? '')."\n[DIBATALKAN] oleh ".auth()->user()->name.' pada '.now()->translatedFormat('d M Y H:i')),
        ]);

        $audit->log('sales.invoice_cancelled', $invoice, "Faktur {$invoice->invoice_number} dibatalkan", $invoice->invoice_number);

        return redirect()->route('invoices.index')->with('status', "Faktur {$invoice->invoice_number} dibatalkan.");
    }
}
