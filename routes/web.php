<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Support\AdminPath;

Route::middleware(['vani.admin', 'auth:staff', 'vani.staff-context'])
    ->prefix(AdminPath::prefix())
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
    });

// Health tổng hợp (Phase 6): LB/giám sát. /up (Laravel) chỉ kiểm tra app boot được.
Route::get('/health', HealthController::class)->middleware('throttle:60,1')->name('health');
