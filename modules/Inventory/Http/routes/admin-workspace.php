<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Admin\ReconciliationController;
use Modules\Inventory\Http\Controllers\Admin\StockController;

// Prefix: /{admin}/inventory — tên route: admin.inventory.*
Route::get('stock', [StockController::class, 'index'])->name('stock.index');
Route::post('stock', [StockController::class, 'change'])->name('stock.change');
Route::post('stock/batch', [StockController::class, 'batch'])->name('stock.batch');
Route::post('stock/bulk', [StockController::class, 'bulk'])->name('stock.bulk');
Route::post('stock/default-location', [StockController::class, 'defaultLocation'])->name('stock.default-location');
Route::get('stock/export', [StockController::class, 'export'])->name('stock.export');
Route::post('stock/import', [StockController::class, 'import'])->name('stock.import');
Route::post('stock/import/{token}', [StockController::class, 'applyImport'])->where('token', '[A-Za-z0-9]{40}')->name('stock.import.apply');
Route::get('movements', [StockController::class, 'movements'])->name('movements.index');

// Kho chính (cửa hàng một kho, ADR-042): sửa tên/địa chỉ. Nhiều kho, chuyển kho: plugin vani.warehouse.
Route::put('stock/location/{location}', [StockController::class, 'updateLocation'])->whereNumber('location')->name('stock.location');

Route::get('reconciliations', [ReconciliationController::class, 'index'])->name('reconciliations.index');
Route::get('reconciliations/{reconciliation}', [ReconciliationController::class, 'show'])->name('reconciliations.show');
