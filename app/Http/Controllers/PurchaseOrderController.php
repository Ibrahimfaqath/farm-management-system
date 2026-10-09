<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\FeedItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with('supplier')
            ->withCount('items')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('po_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->integer('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->latest('order_date')
            ->paginate(15)
            ->withQueryString();

        return view('purchase-orders.index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => $request->only(['search', 'status', 'supplier_id']),
            'statuses' => $this->enumOptions(PurchaseOrderStatus::class),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'openOrders' => PurchaseOrder::query()
                ->whereIn('status', [PurchaseOrderStatus::Approved->value, PurchaseOrderStatus::PartiallyReceived->value])
                ->count(),
        ]);
    }

    public function create(): View
    {
        $purchaseOrder = new PurchaseOrder([
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'status' => PurchaseOrderStatus::Approved,
        ]);

        return view('purchase-orders.create', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'feedItems' => FeedItem::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'price_per_unit']),
        ]);
    }

    public function store(PurchaseOrderRequest $request, AuditLogger $audit): RedirectResponse
    {
        $purchaseOrder = DB::transaction(function () use ($request) {
            return $this->persist(new PurchaseOrder, $request);
        });

        $audit->log(
            'purchase_order.created',
            $purchaseOrder,
            "Pesanan pembelian {$purchaseOrder->po_number} dibuat ke {$purchaseOrder->supplier->name}",
            $purchaseOrder->po_number,
            ['total' => (float) $purchaseOrder->total],
        );

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('status', "Pesanan pembelian {$purchaseOrder->po_number} dibuat. Stok belum berubah sampai barang diterima.");
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'creator', 'items.feedItem', 'goodsReceipts.items']);

        $received = $purchaseOrder->receivedQuantities();

        $lines = $purchaseOrder->items->map(function ($item) use ($received) {
            $receivedQty = (float) ($received[$item->getKey()] ?? 0);
            $ordered = (float) $item->quantity;

            return (object) [
                'item' => $item,
                'ordered' => $ordered,
                'received' => $receivedQty,
                'outstanding' => max(0, round($ordered - $receivedQty, 4)),
                'received_pct' => $ordered > 0 ? round($receivedQty / $ordered * 100, 1) : 0.0,
            ];
        });

        return view('purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
            'lines' => $lines,
            'totalOrdered' => (float) $purchaseOrder->items()->sum('quantity'),
            'totalReceived' => $purchaseOrder->totalReceived(),
            'totalOutstanding' => $purchaseOrder->totalOutstanding(),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        abort_if($purchaseOrder->status !== PurchaseOrderStatus::Draft, 403, 'Pesanan yang sudah disetujui tidak dapat diubah.');

        return view('purchase-orders.edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'feedItems' => FeedItem::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'price_per_unit']),
        ]);
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, AuditLogger $audit): RedirectResponse
    {
        abort_if($purchaseOrder->status !== PurchaseOrderStatus::Draft, 403, 'Pesanan yang sudah disetujui tidak dapat diubah.');

        DB::transaction(function () use ($request, $purchaseOrder): void {
            $purchaseOrder->items()->delete();
            $this->persist($purchaseOrder, $request);
        });

        $audit->log('purchase_order.updated', $purchaseOrder, "Pesanan pembelian {$purchaseOrder->po_number} diperbarui", $purchaseOrder->po_number);

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('status', 'Pesanan pembelian diperbarui.');
    }

    /**
     * Approve a draft order so it can receive goods.
     */
    public function approve(PurchaseOrder $purchaseOrder, AuditLogger $audit): RedirectResponse
    {
        abort_if($purchaseOrder->status !== PurchaseOrderStatus::Draft, 403, 'Pesanan ini bukan berstatus draf.');

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::Approved,
            'approved_by' => request()->user()->getKey(),
            'approved_at' => now(),
        ]);

        $audit->log('purchase_order.approved', $purchaseOrder, "Pesanan {$purchaseOrder->po_number} disetujui", $purchaseOrder->po_number);

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('status', 'Pesanan pembelian disetujui.');
    }

    public function cancel(PurchaseOrder $purchaseOrder, AuditLogger $audit): RedirectResponse
    {
        abort_if(
            $purchaseOrder->goodsReceipts()->where('status', 'posted')->exists(),
            403,
            'Pesanan yang sudah menerima barang tidak dapat dibatalkan.',
        );

        $purchaseOrder->update(['status' => PurchaseOrderStatus::Cancelled]);

        $audit->log('purchase_order.cancelled', $purchaseOrder, "Pesanan {$purchaseOrder->po_number} dibatalkan", $purchaseOrder->po_number);

        return redirect()->route('purchase-orders.index')->with('status', 'Pesanan pembelian dibatalkan.');
    }

    private function persist(PurchaseOrder $purchaseOrder, PurchaseOrderRequest $request): PurchaseOrder
    {
        $data = $request->validated();

        $purchaseOrder->fill([
            'supplier_id' => $data['supplier_id'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'status' => PurchaseOrderStatus::Approved,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->getKey(),
            'approved_by' => $request->user()->getKey(),
            'approved_at' => now(),
        ]);

        $purchaseOrder->po_number ??= $this->nextNumber();
        $purchaseOrder->save();

        foreach ($data['items'] as $item) {
            $purchaseOrder->items()->create([
                'feed_item_id' => $item['feed_item_id'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'batch_hint' => $item['batch_hint'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $purchaseOrder->recalculateTotals();

        return $purchaseOrder->fresh();
    }

    private function nextNumber(): string
    {
        $latest = PurchaseOrder::query()
            ->where('po_number', 'like', 'PO-'.now()->format('%y%m').'%')
            ->orderByDesc('id')
            ->value('po_number');

        $sequence = $latest ? ((int) Str::afterLast($latest, '-')) + 1 : 1;

        return 'PO-'.now()->format('ym').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
