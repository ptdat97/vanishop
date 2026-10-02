<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Support\AdminPath;

Route::middleware(['vani.admin', 'auth:staff', 'vani.staff-context'])
    ->prefix(AdminPath::prefix())
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
    });
