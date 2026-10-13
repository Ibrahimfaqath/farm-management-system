<?php

use App\Http\Controllers\FeedConsumptionController;
use App\Http\Controllers\FeedItemController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('module:feed')->group(function (): void {
    // --- Master data ---
    Route::get('feed-items', [FeedItemController::class, 'index'])->name('feed-items.index');
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');

    Route::middleware('ability:feed.purchase')->group(function (): void {
        Route::get('feed-items/create', [FeedItemController::class, 'create'])->name('feed-items.create');
        Route::post('feed-items', [FeedItemController::class, 'store'])->name('feed-items.store');
        Route::get('feed-items/{feed_item}/edit', [FeedItemController::class, 'edit'])->name('feed-items.edit');
        Route::put('feed-items/{feed_item}', [FeedItemController::class, 'update'])->name('feed-items.update');

        Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('purchase-orders/{purchase_order}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });

    // Declared after `suppliers/create`: Laravel matches in declaration order,
    // so a `{supplier}` show route listed first would swallow `/suppliers/create`.
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');

    Route::get('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');

    // --- Goods receipts ---
    Route::get('goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::middleware('ability:feed.receive')->group(function (): void {
        Route::get('goods-receipts/create', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
        Route::post('goods-receipts', [GoodsReceiptController::class, 'store'])->name('goods-receipts.store');
        Route::post('goods-receipts/{receipt}/post', [GoodsReceiptController::class, 'post'])->name('goods-receipts.post');
        Route::post('goods-receipts/{receipt}/cancel', [GoodsReceiptController::class, 'cancel'])->name('goods-receipts.cancel');
    });
    Route::get('goods-receipts/{receipt}', [GoodsReceiptController::class, 'show'])->name('goods-receipts.show');

    // --- Inventory ---
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/batches', [InventoryController::class, 'batches'])->name('inventory.batches');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::post('inventory/adjustments', [InventoryController::class, 'adjust'])
        ->middleware('ability:feed.adjust')->name('inventory.adjust');

    // --- Consumption ---
    Route::get('feed-consumptions', [FeedConsumptionController::class, 'index'])->name('feed-consumptions.index');

    // `create` is declared before `show` on purpose: Laravel matches in
    // declaration order, so `feed-consumptions/{consumption}` listed first
    // swallowed `/feed-consumptions/create` and turned the page into a 404.
    Route::middleware('ability:feed.consume')->group(function (): void {
        Route::get('feed-consumptions/create', [FeedConsumptionController::class, 'create'])->name('feed-consumptions.create');
        Route::post('feed-consumptions', [FeedConsumptionController::class, 'store'])->name('feed-consumptions.store');
        Route::post('feed-consumptions/{consumption}/reverse', [FeedConsumptionController::class, 'reverse'])->name('feed-consumptions.reverse');
    });

    Route::get('feed-consumptions/{consumption}', [FeedConsumptionController::class, 'show'])->name('feed-consumptions.show');
});
