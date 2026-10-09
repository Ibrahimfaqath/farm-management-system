<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\Supplier;
use App\Services\FinanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request, FinanceService $finance): View
    {
        $expenses = Expense::query()
            ->with('supplier')
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('description', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->date('date_to')))
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        $monthFrom = $request->date('date_from') ?? now()->startOfMonth()->toDateString();
        $monthTo = $request->date('date_to') ?? now()->toDateString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'filters' => $request->only(['search', 'category', 'date_from', 'date_to']),
            'categories' => $this->enumOptions(ExpenseCategory::class),
            'summary' => $finance->periodSummary($monthFrom, $monthTo),
            'byCategory' => Expense::query()
                ->between($monthFrom, $monthTo)
                ->groupBy('category')
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as records')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => [
                    'category' => $row->category instanceof \BackedEnum ? $row->category->value : $row->category,
                    'total' => (float) $row->total,
                    'records' => (int) $row->records,
                ]),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'expense' => new Expense(['expense_date' => now()->toDateString(), 'payment_method' => 'cash']),
            'categories' => $this->enumOptions(ExpenseCategory::class),
            'methods' => $this->enumOptions(PaymentMethod::class),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(ExpenseRequest $request, FinanceService $finance): RedirectResponse
    {
        $data = $request->validated();

        $result = $finance->recordExpense($data, $request->user());

        return redirect()->route('expenses.index')
            ->with('status', 'Biaya tercatat dan kas keluar Rp'.number_format($result['cash_out'], 0).'.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'categories' => $this->enumOptions(ExpenseCategory::class),
            'methods' => $this->enumOptions(PaymentMethod::class),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
