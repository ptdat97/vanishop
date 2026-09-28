<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Api\CartController;
use Modules\Storefront\Http\Controllers\Api\CategoryController;
use Modules\Storefront\Http\Controllers\Api\CheckoutController;
use Modules\Storefront\Http\Controllers\Api\PaymentController;
use Modules\Storefront\Http\Controllers\Api\ProductController;

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::post('carts', [CartController::class, 'store'])->middleware('throttle:vani-cart-create')->name('carts.store');
Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
Route::post('carts/{cart}/lines', [CartController::class, 'addLine'])->name('carts.lines.store');
Route::patch('carts/{cart}/lines/{line}', [CartController::class, 'updateLine'])->whereNumber('line')->name('carts.lines.update');
Route::delete('carts/{cart}/lines/{line}', [CartController::class, 'removeLine'])->whereNumber('line')->name('carts.lines.destroy');

Route::post('checkout/{cart}/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
Route::post('checkout/{cart}/orders', [CheckoutController::class, 'placeOrder'])->middleware('throttle:vani-checkout')->name('checkout.orders.store');
Route::get('payments/{payment}', [PaymentController::class, 'show'])->where('payment', '[0-9A-Z]{26}')->name('payments.show');
