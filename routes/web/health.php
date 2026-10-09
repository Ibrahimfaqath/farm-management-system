<?php

use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\VaccinationController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:health')->group(function (): void {
    Route::get('health-records', [HealthRecordController::class, 'index'])->name('health-records.index');
    Route::get('vaccinations', [VaccinationController::class, 'index'])->name('vaccinations.index');
    Route::get('treatments', [TreatmentController::class, 'index'])->name('treatments.index');

    Route::middleware('ability:health.manage')->group(function (): void {
        Route::get('health-records/create', [HealthRecordController::class, 'create'])->name('health-records.create');
        Route::post('health-records', [HealthRecordController::class, 'store'])->name('health-records.store');
        Route::get('health-records/{record}/edit', [HealthRecordController::class, 'edit'])->name('health-records.edit');
        Route::put('health-records/{record}', [HealthRecordController::class, 'update'])->name('health-records.update');
        Route::post('health-records/{record}/resolve', [HealthRecordController::class, 'resolve'])->name('health-records.resolve');

        Route::get('vaccinations/create', [VaccinationController::class, 'create'])->name('vaccinations.create');
        Route::post('vaccinations', [VaccinationController::class, 'store'])->name('vaccinations.store');

        Route::get('treatments/create', [TreatmentController::class, 'create'])->name('treatments.create');
        Route::post('treatments', [TreatmentController::class, 'store'])->name('treatments.store');
    });

    Route::get('health-records/{record}', [HealthRecordController::class, 'show'])->name('health-records.show');
});
