<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\BarnController;
use App\Http\Controllers\BreedController;
use App\Http\Controllers\FarmController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:farm')->group(function (): void {
    // --- Farms ---
    Route::get('farms', [FarmController::class, 'index'])->name('farms.index');
    Route::middleware('ability:settings.manage')->group(function (): void {
        Route::get('farms/create', [FarmController::class, 'create'])->name('farms.create');
        Route::post('farms', [FarmController::class, 'store'])->name('farms.store');
        Route::get('farms/{farm}/edit', [FarmController::class, 'edit'])->name('farms.edit');
        Route::put('farms/{farm}', [FarmController::class, 'update'])->name('farms.update');
    });
    Route::get('farms/{farm}', [FarmController::class, 'show'])->name('farms.show');

    // --- Barns ---
    Route::get('barns', [BarnController::class, 'index'])->name('barns.index');
    Route::middleware('ability:animal.manage')->group(function (): void {
        Route::get('barns/create', [BarnController::class, 'create'])->name('barns.create');
        Route::post('barns', [BarnController::class, 'store'])->name('barns.store');
        Route::get('barns/{barn}/edit', [BarnController::class, 'edit'])->name('barns.edit');
        Route::put('barns/{barn}', [BarnController::class, 'update'])->name('barns.update');
    });
    Route::get('barns/{barn}', [BarnController::class, 'show'])->name('barns.show');

    // --- Breeds ---
    Route::get('breeds', [BreedController::class, 'index'])->name('breeds.index');
    Route::middleware('ability:animal.manage')->group(function (): void {
        Route::get('breeds/create', [BreedController::class, 'create'])->name('breeds.create');
        Route::post('breeds', [BreedController::class, 'store'])->name('breeds.store');
        Route::get('breeds/{breed}/edit', [BreedController::class, 'edit'])->name('breeds.edit');
        Route::put('breeds/{breed}', [BreedController::class, 'update'])->name('breeds.update');
    });

    // --- Animals ---
    Route::get('animals', [AnimalController::class, 'index'])->name('animals.index');
    Route::middleware('ability:animal.manage')->group(function (): void {
        Route::get('animals/create', [AnimalController::class, 'create'])->name('animals.create');
        Route::post('animals', [AnimalController::class, 'store'])->name('animals.store');
        Route::get('animals/{animal}/edit', [AnimalController::class, 'edit'])->name('animals.edit');
        Route::put('animals/{animal}', [AnimalController::class, 'update'])->name('animals.update');
    });
    Route::post('animals/{animal}/exit', [AnimalController::class, 'exit'])
        ->middleware('ability:animal.exit')->name('animals.exit');
    Route::post('animals/{animal}/reopen', [AnimalController::class, 'reopen'])
        ->middleware('ability:animal.manage')->name('animals.reopen');
    Route::get('animals/{animal}', [AnimalController::class, 'show'])->name('animals.show');
});
