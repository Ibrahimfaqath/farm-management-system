<?php

use App\Http\Controllers\MilkProductionController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:production')->group(function (): void {
    Route::get('milk-productions', [MilkProductionController::class, 'index'])->name('milk-productions.index');
    Route::get('milk-productions/batch', [MilkProductionController::class, 'batch'])->name('milk-productions.batch');
    Route::post('milk-productions/batch', [MilkProductionController::class, 'storeBatch'])->name('milk-productions.batch.store');

    Route::middleware('ability:production.record')->group(function (): void {
        Route::get('milk-productions/create', [MilkProductionController::class, 'create'])->name('milk-productions.create');
        Route::post('milk-productions', [MilkProductionController::class, 'store'])->name('milk-productions.store');
    });
});
