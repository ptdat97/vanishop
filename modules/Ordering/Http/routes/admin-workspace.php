<?php

use Illuminate\Support\Facades\Route;
use Modules\Ordering\Http\Controllers\Admin\OrderController;

// Prefix: /{admin}/orders — tên route: admin.orders.*
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::post('orders/bulk-confirm', [OrderController::class, 'bulkConfirm'])->name('orders.bulk-confirm');
Route::get('orders/print', [OrderController::class, 'print'])->name('orders.print');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
Route::post('orders/{order}/status-actions/{code}', [OrderController::class, 'statusAction'])->where('code', '[a-z0-9._-]+')->name('orders.status-action');
Route::post('orders/{order}/cancel-lines', [OrderController::class, 'cancelLines'])->name('orders.cancel-lines');
Route::put('orders/{order}/shipping-address', [OrderController::class, 'updateAddress'])->name('orders.shipping-address');
Route::post('orders/{order}/notes', [OrderController::class, 'addNote'])->name('orders.notes');
