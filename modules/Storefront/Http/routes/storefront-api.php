<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Api\CategoryController;
use Modules\Storefront\Http\Controllers\Api\ProductController;

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
