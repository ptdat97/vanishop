<?php

use Illuminate\Support\Facades\Route;
use Plugin\Warehouse\Http\Controllers\Admin\LocationController;
use Plugin\Warehouse\Http\Controllers\Admin\TransferController;

// /{admin}/plugins/vani-warehouse/… — tên route admin.plugins.vani-warehouse.*
Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('locations/create', [LocationController::class, 'create'])->name('locations.create');
Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
Route::get('locations/{location}/edit', [LocationController::class, 'edit'])->whereNumber('location')->name('locations.edit');
Route::put('locations/{location}', [LocationController::class, 'update'])->whereNumber('location')->name('locations.update');

Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
Route::post('transfers/{transfer}/ship', [TransferController::class, 'ship'])->whereNumber('transfer')->name('transfers.ship');
Route::post('transfers/{transfer}/receive', [TransferController::class, 'receive'])->whereNumber('transfer')->name('transfers.receive');
Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->whereNumber('transfer')->name('transfers.cancel');
