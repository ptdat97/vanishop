<?php

use Illuminate\Support\Facades\Route;
use Plugin\Cms\Http\Controllers\StorefrontController;

// /trang/{slug}
Route::get('{slug}', [StorefrontController::class, 'page'])->where('slug', '[a-z0-9-]+')->name('page');
