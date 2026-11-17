<?php

use Illuminate\Support\Facades\Route;
use Plugin\PhoneOtpLogin\Http\Controllers\OtpLoginController;

// /p/vani-phone-otp-login/… — form trong trang đăng nhập (không cần JS). Cùng việc với Storefront API `x/vani-phone-otp-login/*`.
Route::middleware('throttle:vani-customer-auth')->group(function (): void {
    Route::post('otp', [OtpLoginController::class, 'request'])->name('request');
    Route::post('verify', [OtpLoginController::class, 'verify'])->name('verify');
});
