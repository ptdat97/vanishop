<?php

use Illuminate\Support\Facades\Route;
use Plugin\CouponCode\Http\Controllers\CouponApiController;

// /api/storefront/v1/x/vani-coupon-code/… (openapi.json).
Route::get('products/{product}', [CouponApiController::class, 'product'])->whereNumber('product')->name('product');
Route::get('carts/{cart}/wallet', [CouponApiController::class, 'wallet'])->middleware('throttle:60,1')->name('wallet');
Route::middleware('vani.customer')->get('mine', [CouponApiController::class, 'mine'])->name('mine');
