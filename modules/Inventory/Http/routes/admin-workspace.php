<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Admin\ReconciliationController;
use Modules\Inventory\Http\Controllers\Admin\StockController;
use Modules\Inventory\Http\Controllers\Admin\StockCountController;
use Modules\Inventory\Http\Controllers\Admin\StockReceiptController;

// Prefix: /{admin}/inventory — tên route: admin.inventory.*
Route::get('stock', [StockController::class, 'index'])->name('stock.index');
Route::post('stock', [StockController::class, 'change'])->name('stock.change');
Route::post('stock/batch', [StockController::class, 'batch'])->name('stock.batch');
Route::post('stock/bulk', [StockController::class, 'bulk'])->name('stock.bulk');
Route::post('stock/default-location', [StockController::class, 'defaultLocation'])->name('stock.default-location');
Route::get('stock/export', [StockController::class, 'export'])->name('stock.export');
Route::post('stock/import', [StockController::class, 'import'])->name('stock.import');
Route::get('stock/runs/{run}', [StockController::class, 'run'])->where('run', '[0-9a-z-]{36}')->name('stock.runs.show');
Route::post('stock/import/{token}', [StockController::class, 'applyImport'])->where('token', '[A-Za-z0-9]{40}')->name('stock.import.apply');
Route::get('movements', [StockController::class, 'movements'])->name('movements.index');
Route::get('counts', [StockCountController::class, 'index'])->name('counts.index');
Route::post('counts', [StockCountController::class, 'store'])->name('counts.store');
Route::get('counts/{count}', [StockCountController::class, 'show'])->whereNumber('count')->name('counts.show');
Route::post('counts/{count}/lines', [StockCountController::class, 'record'])->whereNumber('count')->name('counts.record');
Route::put('counts/{count}/lines/{variant}', [StockCountController::class, 'line'])->whereNumber(['count', 'variant'])->name('counts.line');
Route::post('counts/{count}/apply', [StockCountController::class, 'apply'])->whereNumber('count')->name('counts.apply');
Route::post('counts/{count}/cancel', [StockCountController::class, 'cancel'])->whereNumber('count')->name('counts.cancel');
Route::get('receipts', [StockReceiptController::class, 'index'])->name('receipts.index');
Route::get('receipts/create', [StockReceiptController::class, 'create'])->name('receipts.create');
Route::post('receipts', [StockReceiptController::class, 'store'])->name('receipts.store');
Route::post('receipts/parse', [StockReceiptController::class, 'parse'])->name('receipts.parse');
Route::get('receipts/{receipt}', [StockReceiptController::class, 'show'])->whereNumber('receipt')->name('receipts.show');
Route::put('receipts/{receipt}', [StockReceiptController::class, 'update'])->whereNumber('receipt')->name('receipts.update');
Route::post('receipts/{receipt}/receive', [StockReceiptController::class, 'receive'])->whereNumber('receipt')->name('receipts.receive');
Route::post('receipts/{receipt}/cancel', [StockReceiptController::class, 'cancel'])->whereNumber('receipt')->name('receipts.cancel');

// Kho chính (cửa hàng một kho, ADR-042): sửa tên/địa chỉ. Nhiều kho, chuyển kho: plugin vani.warehouse.
Route::put('stock/location/{location}', [StockController::class, 'updateLocation'])->whereNumber('location')->name('stock.location');

Route::get('reconciliations', [ReconciliationController::class, 'index'])->name('reconciliations.index');
Route::get('reconciliations/{reconciliation}', [ReconciliationController::class, 'show'])->name('reconciliations.show');
