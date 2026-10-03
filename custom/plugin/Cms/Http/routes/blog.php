<?php

use Illuminate\Support\Facades\Route;
use Plugin\Cms\Http\Controllers\StorefrontController;

// /tin-tuc, /tin-tuc/{slug}
Route::get('/', [StorefrontController::class, 'blog'])->name('blog');
Route::get('{slug}', [StorefrontController::class, 'post'])->where('slug', '[a-z0-9-]+')->name('post');
