<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Api\AccountOrderController;
use Modules\Storefront\Http\Controllers\Api\AddressController;
use Modules\Storefront\Http\Controllers\Api\BrandController;
use Modules\Storefront\Http\Controllers\Api\CartController;
use Modules\Storefront\Http\Controllers\Api\CategoryController;
use Modules\Storefront\Http\Controllers\Api\CheckoutController;
use Modules\Storefront\Http\Controllers\Api\OrderController;
use Modules\Storefront\Http\Controllers\Api\PaymentController;
use Modules\Storefront\Http\Controllers\Api\ProductController;

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('brands/{slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('address/provinces', [AddressController::class, 'provinces'])->name('address.provinces');
Route::get('address/provinces/{province}/wards', [AddressController::class, 'wards'])->where('province', '[0-9A-Za-z_-]{1,16}')->name('address.wards');

// Giỏ & checkout: dùng được khi chưa đăng nhập (token giỏ) hoặc đã đăng nhập (Bearer, giỏ của khách).
Route::middleware('vani.customer:optional')->group(function () {
    Route::post('carts', [CartController::class, 'store'])->middleware('throttle:vani-cart-create')->name('carts.store');
    Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
    Route::post('carts/{cart}/lines', [CartController::class, 'addLine'])->name('carts.lines.store');
    Route::patch('carts/{cart}/lines/{line}', [CartController::class, 'updateLine'])->whereNumber('line')->name('carts.lines.update');
    Route::delete('carts/{cart}/lines/{line}', [CartController::class, 'removeLine'])->whereNumber('line')->name('carts.lines.destroy');

    Route::post('checkout/{cart}/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
    Route::post('checkout/{cart}/orders', [CheckoutController::class, 'placeOrder'])->middleware('throttle:vani-checkout')->name('checkout.orders.store');
});

Route::get('payments/{payment}', [PaymentController::class, 'show'])->where('payment', '[0-9A-Z]{26}')->name('payments.show');

Route::get('orders/track', [OrderController::class, 'track'])->middleware('throttle:vani-order-track')->name('orders.track');
Route::get('orders/{order}', [OrderController::class, 'show'])->where('order', '[0-9A-Z]{26}')->name('orders.show');
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->where('order', '[0-9A-Z]{26}')->name('orders.cancel');
Route::post('orders/{order}/returns', [OrderController::class, 'requestReturn'])->where('order', '[0-9A-Z]{26}')->middleware('throttle:vani-checkout')->name('orders.returns.store');
Route::post('orders/{order}/returns/{return}/cancel', [OrderController::class, 'cancelReturn'])->where(['order' => '[0-9A-Z]{26}', 'return' => '[0-9A-Z]{26}'])->name('orders.returns.cancel');

Route::middleware('vani.customer')->group(function () {
    Route::get('me/cart', [AccountOrderController::class, 'cart'])->name('me.cart');
    Route::get('me/orders', [AccountOrderController::class, 'index'])->name('me.orders.index');
    Route::get('me/orders/{order}', [AccountOrderController::class, 'show'])->where('order', '[0-9A-Z]{26}')->name('me.orders.show');
    Route::post('me/orders/{order}/cancel', [AccountOrderController::class, 'cancel'])->where('order', '[0-9A-Z]{26}')->name('me.orders.cancel');
});
