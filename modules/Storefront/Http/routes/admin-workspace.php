<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Admin\HomeBlocksController;
use Modules\Storefront\Http\Controllers\Admin\MaintenancePreviewController;
use Modules\Storefront\Http\Controllers\Admin\MenuController;
use Modules\Storefront\Http\Controllers\Admin\ThemeSettingsController;

Route::get('home-blocks', [HomeBlocksController::class, 'edit'])->name('home-blocks.edit');
Route::put('home-blocks', [HomeBlocksController::class, 'update'])->name('home-blocks.update');
Route::delete('home-blocks', [HomeBlocksController::class, 'reset'])->name('home-blocks.reset');
Route::get('maintenance-preview', MaintenancePreviewController::class)->name('maintenance.preview');

Route::get('menus', [MenuController::class, 'edit'])->name('menus.edit');
Route::get('menus/links', [MenuController::class, 'links'])->name('menus.links');
Route::put('menus/{location}', [MenuController::class, 'update'])->where('location', '[a-z][a-z0-9_]*')->name('menus.update');
Route::post('menus/{location}/publish', [MenuController::class, 'publish'])->where('location', '[a-z][a-z0-9_]*')->name('menus.publish');
Route::post('menus/{location}/discard', [MenuController::class, 'discard'])->where('location', '[a-z][a-z0-9_]*')->name('menus.discard');
Route::post('menus/{location}/restore/{version}', [MenuController::class, 'restore'])->where(['location' => '[a-z][a-z0-9_]*', 'version' => '[0-9]+'])->name('menus.restore');

Route::get('theme-settings', [ThemeSettingsController::class, 'edit'])->name('theme-settings.edit');
Route::put('theme-settings', [ThemeSettingsController::class, 'update'])->name('theme-settings.update');
