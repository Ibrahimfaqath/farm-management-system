<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\SupplierPaymentRequest;
use App\Models\GoodsReceipt;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\FinanceService;
use App\Support\Number;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function index(Request $request, FinanceService $finance): View
    {
        $payments = SupplierPayment::query()
            ->with(['supplier', 'goodsReceipt', 'recorder'])
            ->when($request->integer('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->latest('payment_date')
            ->paginate(20)
            ->withQueryString();

        return view('supplier-payments.index', [
            'payments' => $payments,
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'filters' => $request->only(['supplier_id']),
            'outstanding' => $finance->supplierOutstanding(),
            'totalOutstanding' => array_sum(array_column($finance->supplierOutstanding(), 'outstanding')),
        ]);
    }

    public function create(Request $request): View
    {
        $receipt = $request->integer('goods_receipt_id')
            ? GoodsReceipt::query()->with('supplier')->findOrFail($request->integer('goods_receipt_id'))
            : null;

        return view('supplier-payments.create', [
            'payment' => new SupplierPayment(['payment_date' => now()->toDateString()]),
            'receipt' => $receipt,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'openReceipts' => GoodsReceipt::query()
                ->where('status', 'posted')
                ->with('supplier')
                ->withSum('payments', 'amount')
                ->get()
                ->filter(fn ($receipt) => (float) $receipt->total_amount - (float) $receipt->payments_sum_amount > 0)
                ->mapWithKeys(fn ($r) => [$r->id => $r->receipt_number.' · '.$r->supplier->name.' (sisa Rp'.Number::format(max(0, (float) $r->total_amount - (float) $r->payments_sum_amount), 0).')']),
            'methods' => $this->enumOptions(PaymentMethod::class),
        ]);
    }

    public function store(SupplierPaymentRequest $request, FinanceService $finance): RedirectResponse
    {
        $data = $request->validated();

        $result = $finance->recordSupplierPayment($data, $request->user());

        $message = 'Kas keluar Rp'.Number::format($result['cash_out'], 0);

        if ($result['ap_before'] > 0) {
            $message .= '. Utang penerimaan: Rp'.Number::format($result['ap_before'], 0)
                .' → Rp'.Number::format($result['ap_after'], 0);
        }

        return redirect()->route('supplier-payments.index')->with('status', $message.'.');
    }
}
