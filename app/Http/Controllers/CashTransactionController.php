<?php

namespace App\Http\Controllers;

use App\Enums\CashCategory;
use App\Enums\CashDirection;
use App\Enums\PaymentMethod;
use App\Models\CashTransaction;
use App\Services\AuditLogger;
use App\Services\FinanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CashTransactionController extends Controller
{
    public function index(Request $request, FinanceService $finance): View
    {
        $transactions = CashTransaction::query()
            ->with('creator')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('description', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->string('direction')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->date('date_to')))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $from = $request->date('date_from') ?? now()->startOfMonth()->toDateString();
        $to = $request->date('date_to') ?? now()->toDateString();

        return view('cash-transactions.index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'direction', 'category', 'date_from', 'date_to']),
            'directions' => $this->enumOptions(CashDirection::class),
            'categories' => $this->enumOptions(CashCategory::class),
            'balance' => $finance->cashBalance(),
            'summary' => $finance->periodSummary($from, $to),
        ]);
    }

    /**
     * Manual cash entry for movements that are not expenses or customer receipts.
     */
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::in(CashCategory::values())],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
            'method' => ['required', Rule::in(PaymentMethod::values())],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'category.in' => 'Pilih jenis transaksi kas.',
        ]);

        $category = CashCategory::from($data['category']);

        $blocked = [
            CashCategory::CustomerReceipt->value,
            CashCategory::Expense->value,
            CashCategory::SupplierPayment->value,
            CashCategory::OtherIncome->value,
        ];

        abort_if(in_array($data['category'], $blocked, true), 422, 'Jenis transaksi ini dicatat lewat modulnya masing-masing.');

        $transaction = DB::transaction(function () use ($data, $category, $request): CashTransaction {
            return CashTransaction::create([
                'transaction_number' => $this->nextNumber(),
                'transaction_date' => $data['transaction_date'],
                'direction' => $category->defaultDirection(),
                'category' => $category,
                'amount' => round((float) $data['amount'], 2),
                'description' => $data['description'],
                'method' => $data['method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->getKey(),
            ]);
        });

        $audit->log('finance.cash_recorded', $transaction, $data['description'], $transaction->transaction_number);

        return redirect()->route('cash-transactions.index')
            ->with('status', 'Transaksi kas dicatat.');
    }

    private function nextNumber(): string
    {
        $latest = CashTransaction::query()
            ->where('transaction_number', 'like', 'TRX-%')
            ->orderByDesc('id')
            ->value('transaction_number');

        $sequence = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return 'TRX-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
