<?php

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Http\Controllers\Admin\PriceListController;

// Prefix: /{admin}/pricing — tên route: admin.pricing.*
Route::resource('price-lists', PriceListController::class)->except('show')->parameters(['price-lists' => 'priceList']);
Route::get('price-lists/{priceList}/prices', [PriceListController::class, 'prices'])->name('price-lists.prices');
Route::put('price-lists/{priceList}/prices', [PriceListController::class, 'updatePrices'])->name('price-lists.prices.update');
