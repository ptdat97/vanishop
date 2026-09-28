<?php

use Illuminate\Support\Facades\Route;
use Modules\Ordering\Http\Controllers\Admin\OrderController;

Route::get('orders', [OrderController::class, 'home'])->name('orders.home');
