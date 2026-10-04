<?php

use Illuminate\Support\Facades\Route;
use Modules\Ordering\Http\Controllers\Admin\OrderController;

// Prefix: /{admin}/orders — tên route: admin.orders.*
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('orders/{order}/cancel-lines', [OrderController::class, 'cancelLines'])->name('orders.cancel-lines');
Route::put('orders/{order}/shipping-address', [OrderController::class, 'updateAddress'])->name('orders.shipping-address');
Route::post('orders/{order}/notes', [OrderController::class, 'addNote'])->name('orders.notes');
