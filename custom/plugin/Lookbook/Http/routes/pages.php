<?php

use Illuminate\Support\Facades\Route;
use Plugin\Lookbook\Http\Controllers\StorefrontController;

// /lookbook, /lookbook/{slug} — trang công khai, cache CDN được.
Route::get('/', [StorefrontController::class, 'index'])->name('index');
Route::get('{slug}', [StorefrontController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('show');
