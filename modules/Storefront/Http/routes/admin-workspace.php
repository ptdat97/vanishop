<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Admin\HomeBlocksController;

Route::get('home-blocks', [HomeBlocksController::class, 'edit'])->name('home-blocks.edit');
Route::put('home-blocks', [HomeBlocksController::class, 'update'])->name('home-blocks.update');
Route::delete('home-blocks', [HomeBlocksController::class, 'reset'])->name('home-blocks.reset');
