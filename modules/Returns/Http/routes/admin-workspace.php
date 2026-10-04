<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\Admin\ReturnController;

// Prefix: /{admin}/returns — tên route: admin.returns.*
Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
Route::get('returns/create/{order}', [ReturnController::class, 'create'])->whereNumber('order')->name('returns.create');
Route::post('returns', [ReturnController::class, 'store'])->name('returns.store');
Route::get('returns/{return}', [ReturnController::class, 'show'])->name('returns.show');
Route::post('returns/{return}/transition', [ReturnController::class, 'transition'])->name('returns.transition');
Route::post('returns/{return}/receive', [ReturnController::class, 'receive'])->name('returns.receive');
Route::post('returns/{return}/resolve', [ReturnController::class, 'resolve'])->name('returns.resolve');
