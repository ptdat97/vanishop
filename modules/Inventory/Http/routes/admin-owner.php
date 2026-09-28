<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Admin\LocationController;
use Modules\Inventory\Http\Controllers\Admin\StockController;

Route::get('inventory', [StockController::class, 'home'])->name('inventory.home');
Route::resource('inventory/locations', LocationController::class)->except(['show', 'destroy'])->names('inventory.locations');
