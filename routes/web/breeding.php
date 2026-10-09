<?php

use App\Http\Controllers\BirthRecordController;
use App\Http\Controllers\BreedingRecordController;
use App\Http\Controllers\PregnancyCheckController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:breeding')->group(function (): void {
    Route::get('breeding-records', [BreedingRecordController::class, 'index'])->name('breeding-records.index');
    Route::get('pregnancy-checks', [PregnancyCheckController::class, 'index'])->name('pregnancy-checks.index');
    Route::get('birth-records', [BirthRecordController::class, 'index'])->name('birth-records.index');

    Route::middleware('ability:breeding.manage')->group(function (): void {
        Route::get('breeding-records/create', [BreedingRecordController::class, 'create'])->name('breeding-records.create');
        Route::post('breeding-records', [BreedingRecordController::class, 'store'])->name('breeding-records.store');

        Route::get('pregnancy-checks/create', [PregnancyCheckController::class, 'create'])->name('pregnancy-checks.create');
        Route::post('pregnancy-checks', [PregnancyCheckController::class, 'store'])->name('pregnancy-checks.store');

        Route::get('birth-records/create', [BirthRecordController::class, 'create'])->name('birth-records.create');
        Route::post('birth-records', [BirthRecordController::class, 'store'])->name('birth-records.store');
    });

    Route::get('breeding-records/{record}', [BreedingRecordController::class, 'show'])->name('breeding-records.show');
    Route::get('birth-records/{birth}', [BirthRecordController::class, 'show'])->name('birth-records.show');
});
