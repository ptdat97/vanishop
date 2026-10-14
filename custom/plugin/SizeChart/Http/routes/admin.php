<?php

use Illuminate\Support\Facades\Route;
use Plugin\SizeChart\Http\Controllers\SizeChartController;

Route::get('/', [SizeChartController::class, 'index'])->name('index');
Route::get('create', [SizeChartController::class, 'create'])->name('create');
Route::post('/', [SizeChartController::class, 'store'])->name('store');
Route::get('{id}', [SizeChartController::class, 'edit'])->whereNumber('id')->name('edit');
Route::put('{id}', [SizeChartController::class, 'update'])->whereNumber('id')->name('update');
Route::delete('{id}', [SizeChartController::class, 'destroy'])->whereNumber('id')->name('destroy');
