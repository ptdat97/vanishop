<?php

use Illuminate\Support\Facades\Route;
use Modules\Extension\Http\Controllers\Admin\PluginController;
use Modules\Extension\Http\Controllers\Admin\SettingsController;

Route::get('plugins', [PluginController::class, 'index'])->name('plugins.index');
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
