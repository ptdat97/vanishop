<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Admin\AdminPathController;

// Đường dẫn Admin (ADR-020): chỉ super admin.
Route::get('security/admin-path', [AdminPathController::class, 'edit'])->name('security.admin-path.edit');
Route::put('security/admin-path', [AdminPathController::class, 'update'])->middleware('throttle:10,1')->name('security.admin-path.update');
