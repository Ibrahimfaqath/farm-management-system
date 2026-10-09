<?php

use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\SupplierPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:finance')->group(function (): void {
    Route::middleware('ability:finance.manage')->group(function (): void {
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');

        Route::get('incomes', [IncomeController::class, 'index'])->name('incomes.index');
        Route::get('incomes/create', [IncomeController::class, 'create'])->name('incomes.create');
        Route::post('incomes', [IncomeController::class, 'store'])->name('incomes.store');

        Route::get('cash-transactions', [CashTransactionController::class, 'index'])->name('cash-transactions.index');
        Route::post('cash-transactions', [CashTransactionController::class, 'store'])->name('cash-transactions.store');

        Route::get('supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
        Route::get('supplier-payments/create', [SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
        Route::post('supplier-payments', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
    });
});
