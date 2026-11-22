<?php

use Illuminate\Support\Facades\Route;
use Plugin\Wishlist\Http\Controllers\WishlistController;

Route::get('/', [WishlistController::class, 'guest'])->name('guest');
Route::post('/items', [WishlistController::class, 'preview'])->middleware('throttle:60,1')->name('preview');
