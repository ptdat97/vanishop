<?php

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Http\Controllers\Admin\PriceListController;

Route::get('pricing', [PriceListController::class, 'home'])->name('pricing.home');
