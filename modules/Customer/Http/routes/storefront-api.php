<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Api\AccountController;
use Modules\Customer\Http\Controllers\Api\AuthController;

// Prefix: /api/storefront/v1 — tên route: api.storefront.v1.*
Route::middleware('throttle:vani-customer-auth')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/register/verify', [AuthController::class, 'verifyRegistration'])->name('auth.register.verify');
    Route::post('auth/email/verify', [AuthController::class, 'verifyEmail'])->name('auth.email.verify');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('auth/password/forgot', [AuthController::class, 'forgotPassword'])->name('auth.password.forgot');
    Route::post('auth/password/reset', [AuthController::class, 'resetPassword'])->name('auth.password.reset');
    Route::get('auth/social', [AuthController::class, 'socialProviders'])->name('auth.social.index');
    Route::post('auth/social/{provider}/start', [AuthController::class, 'socialStart'])->where('provider', '[a-z0-9_.-]+')->name('auth.social.start');
    Route::post('auth/social/{provider}/complete', [AuthController::class, 'socialComplete'])->where('provider', '[a-z0-9_.-]+')->name('auth.social.complete');
});

Route::middleware('vani.customer')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('me', [AccountController::class, 'show'])->name('me.show');
    Route::patch('me', [AccountController::class, 'update'])->name('me.update');
    Route::get('me/delete', [AccountController::class, 'deletion'])->name('me.deletion');
    Route::post('me/delete', [AccountController::class, 'destroy'])->middleware('throttle:vani-customer-auth')->name('me.destroy');
    Route::put('me/password', [AccountController::class, 'password'])->middleware('throttle:vani-customer-auth')->name('me.password');
    Route::post('me/email/verification', [AccountController::class, 'sendEmailVerification'])->middleware('throttle:vani-customer-auth')->name('me.email.verification');
    Route::get('me/export', [AccountController::class, 'export'])->name('me.export');
    Route::get('me/addresses', [AccountController::class, 'addresses'])->name('me.addresses.index');
    Route::post('me/addresses', [AccountController::class, 'storeAddress'])->name('me.addresses.store');
    Route::patch('me/addresses/{address}', [AccountController::class, 'updateAddress'])->whereNumber('address')->name('me.addresses.update');
    Route::delete('me/addresses/{address}', [AccountController::class, 'destroyAddress'])->whereNumber('address')->name('me.addresses.destroy');
    Route::get('me/consents', [AccountController::class, 'consents'])->name('me.consents.index');
    Route::put('me/consents', [AccountController::class, 'updateConsent'])->name('me.consents.update');
});
