<?php

namespace App\Http\Controllers;

use App\Enums\SalesOrderStatus;
use App\Http\Requests\SalesOrderRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\AuditLogger;
use App\Services\FinanceService;
use App\Support\Number;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = SalesOrder::query()
            ->with('customer')
            ->withCount('items')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('so_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->latest('order_date')
            ->paginate(15)
            ->withQueryString();

        return view('sales-orders.index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'customer_id']),
            'statuses' => $this->enumOptions(SalesOrderStatus::class),
            'customers' => Customer::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->integer('customer_id')
            ? Customer::query()->findOrFail($request->integer('customer_id'))
            : null;

        return view('sales-orders.create', [
            'salesOrder' => new SalesOrder(['order_date' => now()->toDateString(), 'status' => SalesOrderStatus::Confirmed]),
            'customer' => $customer,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get()
                ->mapWithKeys(fn ($c) => [$c->id => $c->name.' · '.$c->payment_terms_days.' hari']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'default_price']),
        ]);
    }

    public function store(SalesOrderRequest $request, AuditLogger $audit): RedirectResponse
    {
        $order = DB::transaction(function () use ($request): SalesOrder {
            return $this->persist(new SalesOrder, $request);
        });

        $audit->log(
            'sales.order_created',
            $order,
            "Pesanan penjualan {$order->so_number} untuk {$order->customer->name}",
            $order->so_number,
            ['total' => (float) $order->total],
        );

        return redirect()->route('sales-orders.show', $order)
            ->with('status', "Pesanan penjualan {$order->so_number} dibuat. Buat faktur untuk mencatat pendapatan dan piutang.");
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['customer', 'items.product', 'invoice', 'creator']);

        return view('sales-orders.show', ['salesOrder' => $salesOrder]);
    }

    /**
     * Turn a confirmed order into an invoice. Revenue and receivable are
     * recognised; cash is untouched until payment.
     */
    public function invoice(Request $request, SalesOrder $salesOrder, FinanceService $finance, AuditLogger $audit): RedirectResponse
    {
        abort_if($salesOrder->invoice, 403, 'Pesanan ini sudah memiliki faktur.');
        abort_if($salesOrder->status === SalesOrderStatus::Cancelled, 403, 'Pesanan dibatalkan tidak dapat difaktur.');

        $data = $request->validate([
            'invoice_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:180'],
        ]);

        $salesOrder->loadMissing('items');
        $terms = $data['payment_terms_days'] ?? $salesOrder->customer->payment_terms_days;

        $result = $finance->createInvoice([
            'customer_id' => $salesOrder->customer_id,
            'sales_order_id' => $salesOrder->getKey(),
            'invoice_date' => $data['invoice_date'],
            'payment_terms_days' => $terms,
            'subtotal' => (float) $salesOrder->subtotal,
            'discount_amount' => (float) $salesOrder->discount_amount,
            'tax_amount' => (float) $salesOrder->tax_amount,
            'notes' => $salesOrder->notes,
        ], $request->user());

        $salesOrder->update(['status' => SalesOrderStatus::Delivered]);

        $invoice = $result['invoice'];

        return redirect()->route('invoices.show', $invoice)->with([
            'status' => "Faktur {$invoice->invoice_number} dibuat. Piutang bertambah Rp".Number::format($result['receivable_added'], 0).'; kas belum berubah.',
        ]);
    }

    private function persist(SalesOrder $salesOrder, SalesOrderRequest $request): SalesOrder
    {
        $data = $request->validated();

        $salesOrder->fill([
            'customer_id' => $data['customer_id'],
            'order_date' => $data['order_date'],
            'status' => SalesOrderStatus::Confirmed,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->getKey(),
        ]);

        $salesOrder->so_number ??= $this->nextNumber();
        $salesOrder->save();

        $salesOrder->items()->delete();

        foreach ($data['items'] as $item) {
            $salesOrder->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $salesOrder->recalculateTotals();

        return $salesOrder->fresh();
    }

    private function nextNumber(): string
    {
        $latest = SalesOrder::query()
            ->where('so_number', 'like', 'SO-%')
            ->orderByDesc('id')
            ->value('so_number');

        $sequence = $latest ? ((int) Str::afterLast($latest, '-')) + 1 : 1;

        return 'SO-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
