<?php

namespace App\Http\Controllers;

use App\Enums\GoodsReceiptStatus;
use App\Http\Requests\GoodsReceiptRequest;
use App\Models\FeedItem;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GoodsReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $receipts = GoodsReceipt::query()
            ->with(['supplier', 'purchaseOrder'])
            ->withCount('items')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('receipt_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->latest('received_date')
            ->paginate(15)
            ->withQueryString();

        return view('goods-receipts.index', [
            'receipts' => $receipts,
            'filters' => $request->only(['search', 'status', 'supplier_id']),
            'statuses' => $this->enumOptions(GoodsReceiptStatus::class),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'draftCount' => GoodsReceipt::where('status', GoodsReceiptStatus::Draft->value)->count(),
            'postedCount' => GoodsReceipt::where('status', GoodsReceiptStatus::Posted->value)->count(),
            'payableTotal' => GoodsReceipt::where('status', GoodsReceiptStatus::Posted->value)
                ->get()
                ->sum(fn (GoodsReceipt $receipt) => $receipt->outstandingAmount()),
        ]);
    }

    public function create(Request $request): View
    {
        $purchaseOrder = PurchaseOrder::query()
            ->with(['supplier', 'items.feedItem'])
            ->when($request->integer('purchase_order_id'), fn ($q, $id) => $q->findOrFail($id))
            ->first();

        $receipt = new GoodsReceipt([
            'received_date' => now()->toDateString(),
            'status' => GoodsReceiptStatus::Posted,
        ]);

        return view('goods-receipts.create', [
            'receipt' => $receipt,
            'purchaseOrder' => $purchaseOrder,
            'openOrders' => PurchaseOrder::query()
                ->with('supplier')
                ->whereIn('status', ['approved', 'partially_received'])
                ->latest('order_date')
                ->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'feedItems' => FeedItem::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'price_per_unit']),
        ]);
    }

    public function store(GoodsReceiptRequest $request, InventoryService $inventory, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();

        $receipt = DB::transaction(function () use ($data, $request): GoodsReceipt {
            $receipt = GoodsReceipt::create([
                'receipt_number' => $this->nextNumber(),
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id']
                    ?? PurchaseOrder::query()->whereKey($data['purchase_order_id'] ?? 0)->value('supplier_id'),
                'received_date' => $data['received_date'],
                'status' => GoodsReceiptStatus::Draft,
                'delivery_note_reference' => $data['delivery_note_reference'] ?? null,
                'quality_notes' => $data['quality_notes'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $request->user()->getKey(),
                'created_by' => $request->user()->getKey(),
            ]);

            foreach ($data['items'] as $item) {
                $feedItem = FeedItem::query()->findOrFail($item['feed_item_id']);

                $receipt->items()->create([
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                    'feed_item_id' => $feedItem->getKey(),
                    'quantity_received' => $item['quantity_received'],
                    'unit' => $feedItem->unit->value,
                    'unit_price' => $item['unit_price'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'quality_grade' => $item['quality_grade'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $receipt->recalculateTotal();

            return $receipt->fresh();
        });

        $audit->log(
            'goods_receipt.created',
            $receipt,
            "Penerimaan barang {$receipt->receipt_number} dicatat",
            $receipt->receipt_number,
        );

        if ($request->shouldPost()) {
            return $this->postAndRedirect($receipt, $inventory, $request);
        }

        return redirect()->route('goods-receipts.show', $receipt)
            ->with('status', "Penerimaan {$receipt->receipt_number} disimpan sebagai draf. Stok belum bertambah.");
    }

    public function show(GoodsReceipt $receipt): View
    {
        $receipt->load(['supplier', 'purchaseOrder', 'receiver', 'items.feedItem', 'items.batch', 'payments']);

        return view('goods-receipts.show', [
            'receipt' => $receipt,
            'totalAmount' => (float) $receipt->total_amount,
            'paidAmount' => (float) $receipt->payments()->sum('amount'),
            'outstanding' => $receipt->outstandingAmount(),
            'movements' => StockMovement::query()
                ->where('source_type', $receipt->getMorphClass())
                ->where('source_id', $receipt->getKey())
                ->with('feedItem', 'batch')
                ->get(),
        ]);
    }

    /**
     * Confirm a draft receipt. Only a posted receipt increases inventory.
     */
    public function post(GoodsReceipt $receipt, InventoryService $inventory, Request $request): RedirectResponse
    {
        abort_if($receipt->status === GoodsReceiptStatus::Posted, 403, 'Penerimaan ini sudah dikonfirmasi.');

        return $this->postAndRedirect($receipt, $inventory, $request);
    }

    public function cancel(GoodsReceipt $receipt, AuditLogger $audit): RedirectResponse
    {
        abort_if($receipt->status === GoodsReceiptStatus::Posted, 403, 'Penerimaan yang sudah dikonfirmasi tidak dapat dibatalkan.');

        $receipt->update(['status' => GoodsReceiptStatus::Cancelled]);

        $audit->log('goods_receipt.cancelled', $receipt, "Penerimaan {$receipt->receipt_number} dibatalkan", $receipt->receipt_number);

        return redirect()->route('goods-receipts.index')->with('status', 'Penerimaan barang dibatalkan.');
    }

    private function postAndRedirect(GoodsReceipt $receipt, InventoryService $inventory, Request $request): RedirectResponse
    {
        $result = $inventory->postGoodsReceipt($receipt, $request->user());

        $receipt->refresh()->load('items');

        $summary = [
            'received' => $receipt->items->sum(fn ($item) => (float) $item->quantity_received),
            'total' => (float) $receipt->total_amount,
        ];

        $poSummary = $result['purchase_order'] ?? null;

        $message = "Penerimaan {$receipt->receipt_number} dikonfirmasi. Stok bertambah atas nama ".collect($receipt->items)
            ->map(fn ($item) => $item->feedItem->name.' '.(float) $item->quantity_received.' '.$item->unit->shortLabel())
            ->implode(', ').'.';

        if ($poSummary && $poSummary['outstanding'] > 0) {
            $message .= " Sisa pesanan belum diterima: {$poSummary['outstanding']}.";
        } elseif ($poSummary) {
            $message .= ' Pesanan pembelian sudah diterima penuh.';
        }

        return redirect()->route('goods-receipts.show', $receipt)->with([
            'status' => $message,
            'receipt_summary' => $summary,
        ]);
    }

    private function nextNumber(): string
    {
        $latest = GoodsReceipt::query()
            ->where('receipt_number', 'like', 'GR-'.now()->format('%y%m').'%')
            ->orderByDesc('id')
            ->value('receipt_number');

        $sequence = $latest ? ((int) Str::afterLast($latest, '-')) + 1 : 1;

        return 'GR-'.now()->format('ym').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
