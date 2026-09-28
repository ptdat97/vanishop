<?php

use Illuminate\Support\Facades\Route;
use Modules\Fulfillment\Http\Controllers\Admin\ShipmentController;

Route::get('fulfillment', [ShipmentController::class, 'home'])->name('fulfillment.home');
