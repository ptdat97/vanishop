<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Admin\ReconciliationController;
use Modules\Inventory\Http\Controllers\Admin\StockController;
use Modules\Inventory\Http\Controllers\Admin\StockTransferController;

// Prefix: /{admin}/inventory — tên route: admin.inventory.*
Route::get('stock', [StockController::class, 'index'])->name('stock.index');
Route::post('stock', [StockController::class, 'change'])->name('stock.change');
Route::get('movements', [StockController::class, 'movements'])->name('movements.index');

Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
Route::post('transfers', [StockTransferController::class, 'store'])->name('transfers.store');
Route::post('transfers/{transfer}/ship', [StockTransferController::class, 'ship'])->name('transfers.ship');
Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
Route::post('transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel');

Route::get('reconciliations', [ReconciliationController::class, 'index'])->name('reconciliations.index');
Route::get('reconciliations/{reconciliation}', [ReconciliationController::class, 'show'])->name('reconciliations.show');
