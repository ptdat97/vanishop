<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Api\AccountController;
use Modules\Customer\Http\Controllers\Api\AuthController;

// Prefix: /api/storefront/v1 — tên route: api.storefront.v1.*
Route::middleware('throttle:vani-customer-auth')->group(function () {
    Route::post('auth/otp/request', [AuthController::class, 'requestOtp'])->name('auth.otp.request');
    Route::post('auth/otp/verify', [AuthController::class, 'verifyOtp'])->name('auth.otp.verify');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware('vani.customer')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('me', [AccountController::class, 'show'])->name('me.show');
    Route::patch('me', [AccountController::class, 'update'])->name('me.update');
    Route::post('me/delete', [AccountController::class, 'destroy'])->middleware('throttle:vani-customer-auth')->name('me.destroy');
    Route::put('me/password', [AccountController::class, 'password'])->middleware('throttle:vani-customer-auth')->name('me.password');
    Route::get('me/export', [AccountController::class, 'export'])->name('me.export');
    Route::get('me/addresses', [AccountController::class, 'addresses'])->name('me.addresses.index');
    Route::post('me/addresses', [AccountController::class, 'storeAddress'])->name('me.addresses.store');
    Route::patch('me/addresses/{address}', [AccountController::class, 'updateAddress'])->whereNumber('address')->name('me.addresses.update');
    Route::delete('me/addresses/{address}', [AccountController::class, 'destroyAddress'])->whereNumber('address')->name('me.addresses.destroy');
    Route::get('me/consents', [AccountController::class, 'consents'])->name('me.consents.index');
    Route::put('me/consents', [AccountController::class, 'updateConsent'])->name('me.consents.update');
});
