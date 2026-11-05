<?php

use Illuminate\Support\Facades\Route;
use Plugin\Wishlist\Http\Controllers\WishlistApiController;

// /api/storefront/v1/x/vani-wishlist/… — khách đăng nhập bằng Bearer (app).
Route::middleware('vani.customer')->group(function (): void {
    Route::get('items', [WishlistApiController::class, 'index'])->name('items.index');
    Route::post('items', [WishlistApiController::class, 'store'])->name('items.store');
    Route::delete('items/{style}', [WishlistApiController::class, 'destroy'])->whereNumber('style')->name('items.destroy');
});
