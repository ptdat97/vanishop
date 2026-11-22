<?php

use Illuminate\Support\Facades\Route;
use Plugin\Bundles\Http\Controllers\Admin\BundleController;

Route::get('/', [BundleController::class, 'index'])->name('index');
Route::get('create', [BundleController::class, 'create'])->name('create');
Route::get('products', [BundleController::class, 'products'])->name('products');
Route::post('/', [BundleController::class, 'store'])->name('store');
Route::get('{bundle}', [BundleController::class, 'edit'])->whereNumber('bundle')->name('edit');
Route::put('{bundle}', [BundleController::class, 'update'])->whereNumber('bundle')->name('update');
Route::delete('{bundle}', [BundleController::class, 'destroy'])->whereNumber('bundle')->name('destroy');
