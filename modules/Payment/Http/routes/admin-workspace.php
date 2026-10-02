<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Admin\PaymentController;

// Prefix: /{admin}/payment — tên route: admin.payment.*
Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
Route::post('payments/{payment}/refunds', [PaymentController::class, 'refund'])->name('payments.refunds.store');
Route::post('refunds/{refund}/complete', [PaymentController::class, 'completeRefund'])->name('refunds.complete');
