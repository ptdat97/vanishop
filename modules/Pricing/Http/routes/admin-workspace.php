<?php

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Http\Controllers\Admin\PriceListController;

// Prefix: /{admin}/pricing — tên route: admin.pricing.*
Route::resource('price-lists', PriceListController::class)->except('show')->parameters(['price-lists' => 'priceList']);
Route::post('price-lists/add-products', [PriceListController::class, 'addProducts'])->name('price-lists.add-products');
Route::get('price-lists/{priceList}/prices', [PriceListController::class, 'prices'])->name('price-lists.prices');
Route::put('price-lists/{priceList}/prices', [PriceListController::class, 'updatePrices'])->name('price-lists.prices.update');
Route::get('price-lists/{priceList}/candidates', [PriceListController::class, 'candidates'])->name('price-lists.candidates');
Route::post('price-lists/{priceList}/bulk/preview', [PriceListController::class, 'previewBulk'])->name('price-lists.bulk.preview');
Route::post('price-lists/{priceList}/bulk', [PriceListController::class, 'applyBulk'])->name('price-lists.bulk');
Route::get('price-lists/{priceList}/export', [PriceListController::class, 'export'])->name('price-lists.export');
Route::post('price-lists/{priceList}/import', [PriceListController::class, 'import'])->name('price-lists.import');
Route::post('price-lists/{priceList}/remove', [PriceListController::class, 'removePrices'])->name('price-lists.remove');
Route::post('base-prices/bulk', [PriceListController::class, 'bulkBasePrice'])->name('base-prices.bulk');
Route::put('base-prices/{styleId}', [PriceListController::class, 'setProductBasePrice'])->whereNumber('styleId')->name('base-prices.style');
