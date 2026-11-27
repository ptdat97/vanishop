<?php

use Illuminate\Support\Facades\Route;
use Plugin\CouponCode\Http\Controllers\Admin\CouponController;

Route::get('/', [CouponController::class, 'index'])->name('index');
Route::get('create', [CouponController::class, 'create'])->name('create');
Route::get('lookup', [CouponController::class, 'lookup'])->name('lookup');
Route::get('customers', [CouponController::class, 'customers'])->name('customers');
Route::post('/', [CouponController::class, 'store'])->name('store');
Route::get('{coupon}', [CouponController::class, 'edit'])->whereNumber('coupon')->name('edit');
Route::put('{coupon}', [CouponController::class, 'update'])->whereNumber('coupon')->name('update');
Route::delete('{coupon}', [CouponController::class, 'destroy'])->whereNumber('coupon')->name('destroy');
Route::post('{coupon}/codes', [CouponController::class, 'generate'])->whereNumber('coupon')->name('generate');
Route::post('{coupon}/issue', [CouponController::class, 'issue'])->whereNumber('coupon')->name('issue');
