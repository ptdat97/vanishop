<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Admin\ProfileController;

// Hồ sơ của tôi (reference-parity §3.3-3): mọi nhân viên đăng nhập Admin, không cần quyền riêng.
Route::get('ho-so', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('ho-so', [ProfileController::class, 'update'])->name('profile.update');
Route::put('ho-so/mat-khau', [ProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('profile.password');
