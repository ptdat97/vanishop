<?php

use Illuminate\Support\Facades\Route;
use Plugin\CouponCode\Http\Controllers\AccountCouponController;

// /tai-khoan/ma-giam-gia, /en/account/vouchers — cần đăng nhập (accountRoutes).
Route::get('/', AccountCouponController::class)->name('index');
