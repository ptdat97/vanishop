<?php

use Illuminate\Support\Facades\Route;
use Modules\Integration\Http\Controllers\Admin\HealthController;

Route::get('integration', [HealthController::class, 'index'])->name('integration.health');
Route::post('integration/replay', [HealthController::class, 'replay'])->name('integration.replay');
Route::post('integration/subscriptions/{subscription}/resume', [HealthController::class, 'resumeSubscription'])->name('integration.subscriptions.resume');
