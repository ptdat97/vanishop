<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Admin\StaffSessionController;
use Modules\Shared\Support\AdminPath;

Route::prefix(AdminPath::prefix())->name('admin.')->middleware('vani.admin')->group(function (): void {
    Route::middleware('guest:staff')->group(function (): void {
        Route::get('login', [StaffSessionController::class, 'create'])->name('login');
        Route::post('login', [StaffSessionController::class, 'store'])->name('login.store');
    });

    Route::post('logout', [StaffSessionController::class, 'destroy'])
        ->middleware(['auth:staff', 'vani.staff-context'])
        ->name('logout');
});
