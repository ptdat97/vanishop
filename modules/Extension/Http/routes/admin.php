<?php

use Illuminate\Support\Facades\Route;
use Modules\Extension\Http\Controllers\Admin\AdminActionController;
use Modules\Extension\Http\Controllers\Admin\PluginController;
use Modules\Extension\Http\Controllers\Admin\ReportController;
use Modules\Extension\Http\Controllers\Admin\SettingsController;

Route::get('plugins', [PluginController::class, 'index'])->name('plugins.index');
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('extensions/{resource}/actions/{plugin}/{key}', AdminActionController::class)
    ->where(['resource' => '[a-z_]+', 'plugin' => '[a-z0-9.-]+', 'key' => '[a-z0-9_.-]+'])
    ->name('extensions.action');
Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('reports/{key}', [ReportController::class, 'show'])->where('key', '[a-z0-9_-]+')->name('reports.show');
Route::get('reports/{key}/export', [ReportController::class, 'export'])->where('key', '[a-z0-9_-]+')->name('reports.export');
