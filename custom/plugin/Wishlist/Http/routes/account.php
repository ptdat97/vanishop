<?php

use Illuminate\Support\Facades\Route;
use Plugin\Wishlist\Http\Controllers\WishlistController;

// /tai-khoan/yeu-thich, /en/account/wishlist — cần đăng nhập (accountRoutes).
Route::get('/', [WishlistController::class, 'index'])->name('index');
