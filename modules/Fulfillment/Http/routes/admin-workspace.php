<?php

use Illuminate\Support\Facades\Route;
use Modules\Fulfillment\Http\Controllers\Admin\ShipmentController;

// Prefix: /{admin}/fulfillment/{brand} — tên route: admin.fulfillment.*
Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');
Route::post('shipments/{shipment}/book', [ShipmentController::class, 'book'])->name('shipments.book');
Route::post('shipments/{shipment}/status', [ShipmentController::class, 'status'])->name('shipments.status');
Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
