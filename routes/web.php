<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::put('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    require __DIR__.'/web/farm.php';
    require __DIR__.'/web/feed.php';
    require __DIR__.'/web/health.php';
    require __DIR__.'/web/breeding.php';
    require __DIR__.'/web/production.php';
    require __DIR__.'/web/sales.php';
    require __DIR__.'/web/finance.php';
    require __DIR__.'/web/reports.php';
});
