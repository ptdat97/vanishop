<?php

use Illuminate\Support\Facades\Route;
use Plugin\Wishlist\Http\Controllers\WishlistController;

// /p/vani-wishlist/toggle: nút tim là form thường khi tắt JS (ADR-025). Có JS: wishlist.js gọi Storefront API
// `x/vani-wishlist/*` bằng phiên cùng nguồn (ADR-044) — không còn JSON riêng ở đây.
Route::middleware('throttle:60,1')->post('toggle', [WishlistController::class, 'toggle'])->name('toggle');
