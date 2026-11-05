<?php

use Illuminate\Support\Facades\Route;
use Plugin\Lookbook\Http\Controllers\Admin\LookbookController;

Route::get('/', [LookbookController::class, 'index'])->name('index');
Route::get('create', [LookbookController::class, 'create'])->name('create');
Route::post('/', [LookbookController::class, 'store'])->name('store');
Route::get('{lookbook}', [LookbookController::class, 'edit'])->whereNumber('lookbook')->name('edit');
Route::put('{lookbook}', [LookbookController::class, 'update'])->whereNumber('lookbook')->name('update');
Route::delete('{lookbook}', [LookbookController::class, 'destroy'])->whereNumber('lookbook')->name('destroy');
