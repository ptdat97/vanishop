<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Admin\StaffPasswordController;
use Modules\Identity\Http\Controllers\Admin\StaffSessionController;
use Modules\Shared\Support\AdminPath;

Route::prefix(AdminPath::prefix())->name('admin.')->middleware('vani.admin')->group(function (): void {
    Route::middleware('guest:staff')->group(function (): void {
        Route::get('login', [StaffSessionController::class, 'create'])->name('login');
        Route::post('login', [StaffSessionController::class, 'store'])->name('login.store');

        // Link một lần qua email (reference-parity §3.3): quên mật khẩu, lời mời đặt mật khẩu lần đầu.
        Route::get('quen-mat-khau', [StaffPasswordController::class, 'forgot'])->name('password.request');
        Route::post('quen-mat-khau', [StaffPasswordController::class, 'sendLink'])->name('password.email');
        Route::get('dat-lai-mat-khau/{token}', [StaffPasswordController::class, 'showReset'])->name('password.reset');
        Route::post('dat-lai-mat-khau', [StaffPasswordController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
        Route::get('loi-moi/{token}', [StaffPasswordController::class, 'showInvitation'])->name('invitation.show');
        Route::post('loi-moi', [StaffPasswordController::class, 'acceptInvitation'])->middleware('throttle:10,1')->name('invitation.accept');
    });

    Route::post('logout', [StaffSessionController::class, 'destroy'])
        ->middleware(['auth:staff', 'vani.staff-context'])
        ->name('logout');
});
