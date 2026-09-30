<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\Admin\NotificationController;

Route::get('notifications/templates', [NotificationController::class, 'templates'])->name('notifications.templates.index');
Route::post('notifications/templates', [NotificationController::class, 'store'])->name('notifications.templates.store');
Route::put('notifications/templates/{template}', [NotificationController::class, 'update'])->name('notifications.templates.update');
Route::delete('notifications/templates/{template}', [NotificationController::class, 'destroy'])->name('notifications.templates.destroy');
Route::get('notifications/logs', [NotificationController::class, 'logs'])->name('notifications.logs');
