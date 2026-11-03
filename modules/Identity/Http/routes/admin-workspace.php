<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Admin\RoleController;
use Modules\Identity\Http\Controllers\Admin\StaffController;

Route::get('/', [StaffController::class, 'index'])->name('index');
Route::get('create', [StaffController::class, 'create'])->name('create');
Route::post('/', [StaffController::class, 'store'])->name('store');

Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->whereNumber('role')->name('roles.edit');
Route::put('roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('roles.update');
Route::delete('roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('roles.destroy');

Route::get('{staff}/edit', [StaffController::class, 'edit'])->whereNumber('staff')->name('edit');
Route::put('{staff}', [StaffController::class, 'update'])->whereNumber('staff')->name('update');
Route::post('{staff}/lock', [StaffController::class, 'lock'])->whereNumber('staff')->name('lock');
Route::post('{staff}/unlock', [StaffController::class, 'unlock'])->whereNumber('staff')->name('unlock');
Route::post('{staff}/resend-invite', [StaffController::class, 'resendInvite'])->whereNumber('staff')->name('resend-invite');
