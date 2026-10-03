<?php

use Illuminate\Support\Facades\Route;
use Plugin\Cms\Http\Controllers\StorefrontController;

// /api/storefront/v1/x/vani-cms/…
Route::get('pages/{slug}', [StorefrontController::class, 'apiPage'])->where('slug', '[a-z0-9-]+')->name('pages.show');
Route::get('posts', [StorefrontController::class, 'apiPosts'])->name('posts.index');
Route::get('posts/{slug}', [StorefrontController::class, 'apiPost'])->where('slug', '[a-z0-9-]+')->name('posts.show');
