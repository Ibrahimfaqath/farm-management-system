<?php

namespace App\Http\Controllers;

use App\Enums\IncomeCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\IncomeRequest;
use App\Models\Income;
use App\Services\FinanceService;
use App\Support\Number;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request): View
    {
        $incomes = Income::query()
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('description', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('income_date', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('income_date', '<=', $request->date('date_to')))
            ->latest('income_date')
            ->paginate(20)
            ->withQueryString();

        return view('incomes.index', [
            'incomes' => $incomes,
            'filters' => $request->only(['search', 'category', 'date_from', 'date_to']),
            'categories' => $this->enumOptions(IncomeCategory::class),
            'monthTotal' => (float) Income::whereDate('income_date', '>=', now()->startOfMonth())->sum('amount'),
        ]);
    }

    public function create(): View
    {
        return view('incomes.create', [
            'income' => new Income(['income_date' => now()->toDateString(), 'payment_method' => 'cash']),
        ] + $this->formOptions());
    }

    public function store(IncomeRequest $request, FinanceService $finance): RedirectResponse
    {
        $data = $request->validated();

        $result = $finance->recordIncome($data, $request->user());

        return redirect()->route('incomes.index')
            ->with('status', 'Pendapatan tercatat dan kas masuk Rp'.Number::format($result['cash_in'], 0).'.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'categories' => $this->enumOptions(IncomeCategory::class),
            'methods' => $this->enumOptions(PaymentMethod::class),
        ];
    }
}
