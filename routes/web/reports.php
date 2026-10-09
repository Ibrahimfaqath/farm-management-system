<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    // --- Reports ---
    Route::middleware('module:reports')->group(function (): void {
        Route::get('reports/animals', [ReportController::class, 'animal'])->name('reports.animal.index');
        Route::get('reports/feed', [ReportController::class, 'feed'])->name('reports.feed.index');
        Route::get('reports/production', [ReportController::class, 'production'])->name('reports.production.index');
        Route::get('reports/financial', [ReportController::class, 'financial'])->name('reports.financial.index');
    });

    // --- Audit trail ---
    Route::middleware('module:reports')->group(function (): void {
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // --- Settings ---
    Route::middleware('module:settings')->group(function (): void {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    });

    Route::middleware('ability:settings.manage')->group(function (): void {
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/users', [SettingController::class, 'saveUser'])->name('settings.users');
    });
});
