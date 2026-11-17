<?php

use Illuminate\Support\Facades\Route;
use Plugin\PhoneOtpLogin\Http\Controllers\OtpLoginApiController;

// /api/storefront/v1/x/vani-phone-otp-login/… — app, headless: gửi mã, đổi mã lấy token Bearer (ADR-024).
Route::middleware('throttle:vani-customer-auth')->group(function (): void {
    Route::post('otp/request', [OtpLoginApiController::class, 'request'])->name('otp.request');
    Route::post('otp/verify', [OtpLoginApiController::class, 'verify'])->name('otp.verify');
});
