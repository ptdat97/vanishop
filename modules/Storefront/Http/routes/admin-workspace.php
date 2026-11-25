<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Admin\BuilderController;
use Modules\Storefront\Http\Controllers\Admin\BuilderDraftController;
use Modules\Storefront\Http\Controllers\Admin\MaintenancePreviewController;
use Modules\Storefront\Http\Controllers\Admin\MenuController;
use Modules\Storefront\Http\Controllers\Admin\ThemeSettingsController;

// Builder chỉ cho trang chủ; trang khác thêm nội dung bằng code tại slot.
Route::get('builder/home', [BuilderController::class, 'edit'])->name('builder.edit');
Route::put('builder/home', [BuilderController::class, 'update'])->name('builder.update');
Route::delete('builder/home', [BuilderController::class, 'reset'])->name('builder.reset');
Route::post('builder/home/{operation}', BuilderDraftController::class)->whereIn('operation', ['preview', 'import'])->middleware('vani.theme')->name('builder.draft');
Route::get('maintenance-preview', MaintenancePreviewController::class)->name('maintenance.preview');

Route::get('menus', [MenuController::class, 'edit'])->name('menus.edit');
Route::get('menus/links', [MenuController::class, 'links'])->name('menus.links');
Route::put('menus/{location}', [MenuController::class, 'update'])->where('location', '[a-z][a-z0-9_]*')->name('menus.update');
Route::post('menus/{location}/publish', [MenuController::class, 'publish'])->where('location', '[a-z][a-z0-9_]*')->name('menus.publish');
Route::post('menus/{location}/discard', [MenuController::class, 'discard'])->where('location', '[a-z][a-z0-9_]*')->name('menus.discard');
Route::post('menus/{location}/restore/{version}', [MenuController::class, 'restore'])->where(['location' => '[a-z][a-z0-9_]*', 'version' => '[0-9]+'])->name('menus.restore');

Route::get('theme-settings', [ThemeSettingsController::class, 'edit'])->name('theme-settings.edit');
Route::put('theme-settings', [ThemeSettingsController::class, 'update'])->name('theme-settings.update');
