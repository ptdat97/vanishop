<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Admin\StockController;

// Prefix: /{admin}/inventory/{brand} — tên route: admin.inventory.*
Route::get('stock', [StockController::class, 'index'])->name('stock.index');
Route::post('stock', [StockController::class, 'change'])->name('stock.change');
Route::get('movements', [StockController::class, 'movements'])->name('movements.index');
