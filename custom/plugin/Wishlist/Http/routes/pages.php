<?php

use Illuminate\Support\Facades\Route;
use Plugin\Wishlist\Http\Controllers\WishlistController;

// /p/vani-wishlist/… (phiên web, không cache). JS gửi X-CSRF-TOKEN lấy từ dữ liệu phiên của trang (vaniSession.csrf_token).
Route::get('ids', [WishlistController::class, 'ids'])->name('ids');
Route::middleware('throttle:60,1')->group(function (): void {
    Route::post('toggle', [WishlistController::class, 'toggle'])->name('toggle');
    Route::post('merge', [WishlistController::class, 'merge'])->name('merge');
});
