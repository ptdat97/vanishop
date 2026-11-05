<?php

use Illuminate\Support\Facades\Route;
use Plugin\Lookbook\Http\Controllers\StorefrontController;

// /api/storefront/v1/x/vani-lookbook/…
Route::get('lookbooks', [StorefrontController::class, 'apiIndex'])->name('lookbooks.index');
Route::get('lookbooks/{slug}', [StorefrontController::class, 'apiShow'])->where('slug', '[a-z0-9-]+')->name('lookbooks.show');
