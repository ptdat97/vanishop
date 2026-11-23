<?php

use Illuminate\Support\Facades\Route;
use Modules\Checkout\Http\Controllers\Admin\AdminOrderController;

// Tạo đơn trong Admin (stabilization A8) — /{admin}/orders/new, tên route: admin.orders.new*
Route::get('orders/new', [AdminOrderController::class, 'create'])->name('orders.new');
Route::get('orders/new/variants', [AdminOrderController::class, 'variants'])->name('orders.new.variants');
Route::post('orders/new/variants', [AdminOrderController::class, 'resolveVariants'])->name('orders.new.variants.resolve');
Route::post('orders/new/quote', [AdminOrderController::class, 'quote'])->name('orders.new.quote');
Route::post('orders/new', [AdminOrderController::class, 'store'])->name('orders.new.store');
