<?php

use Illuminate\Support\Facades\Route;
use Plugin\VnPay\Http\Controllers\ReturnController;

// GET /p/vani-vnpay/return — VNPay chuyển khách về sau khi thanh toán.
Route::get('return', ReturnController::class)->name('return');
