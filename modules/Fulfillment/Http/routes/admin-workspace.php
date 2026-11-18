<?php

use Illuminate\Support\Facades\Route;
use Modules\Fulfillment\Http\Controllers\Admin\CodStatementController;
use Modules\Fulfillment\Http\Controllers\Admin\ShipmentController;

// Prefix: /{admin}/fulfillment — tên route: admin.fulfillment.*
Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');
Route::post('shipments/bulk-create', [ShipmentController::class, 'bulkCreate'])->name('shipments.bulk-create');
Route::post('shipments/bulk-push', [ShipmentController::class, 'bulkPush'])->name('shipments.bulk-push');
Route::post('shipments/{shipment}/push', [ShipmentController::class, 'push'])->name('shipments.push');
Route::post('shipments/{shipment}/split', [ShipmentController::class, 'split'])->name('shipments.split');
Route::post('shipments/bulk-hand-over', [ShipmentController::class, 'bulkHandOver'])->name('shipments.bulk-hand-over');
Route::get('shipments/export', [ShipmentController::class, 'export'])->name('shipments.export');
Route::post('shipments/import-tracking', [ShipmentController::class, 'importTracking'])->name('shipments.import-tracking');
Route::post('shipments/{shipment}/book', [ShipmentController::class, 'book'])->name('shipments.book');
Route::post('shipments/{shipment}/status', [ShipmentController::class, 'status'])->name('shipments.status');
Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
Route::post('cod-statements/preview', [CodStatementController::class, 'preview'])->name('cod-statements.preview');
Route::post('cod-statements/{token}/apply', [CodStatementController::class, 'apply'])->where('token', '[A-Za-z0-9]{40}')->name('cod-statements.apply');
