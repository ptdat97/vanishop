<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Api\GatewayCallbackController;

// Prefix: /api/payments — tên route: api.payments.*
Route::match(['get', 'post'], '{gateway}/callback', GatewayCallbackController::class)->where('gateway', '[a-z0-9_.-]+')->name('callback');
