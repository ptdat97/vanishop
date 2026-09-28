<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Admin\PaymentController;

Route::get('payment', [PaymentController::class, 'home'])->name('payment.home');
