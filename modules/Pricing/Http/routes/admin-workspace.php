<?php

use Illuminate\Support\Facades\Route;
use Modules\Pricing\Http\Controllers\Admin\PriceHistoryController;
use Modules\Pricing\Http\Controllers\Admin\PriceListController;

// Prefix: /{admin}/pricing — tên route: admin.pricing.*
Route::resource('price-lists', PriceListController::class)->except('show')->parameters(['price-lists' => 'priceList']);
Route::post('price-lists/add-products', [PriceListController::class, 'addProducts'])->name('price-lists.add-products');
Route::post('price-lists/bulk-from-products/preview', [PriceListController::class, 'previewBulkFromProducts'])->name('price-lists.bulk-from-products.preview');
Route::post('price-lists/bulk-from-products', [PriceListController::class, 'applyBulkFromProducts'])->name('price-lists.bulk-from-products');
Route::get('price-lists/{priceList}/prices', [PriceListController::class, 'prices'])->name('price-lists.prices');
Route::put('price-lists/{priceList}/prices', [PriceListController::class, 'updatePrices'])->name('price-lists.prices.update');
Route::get('price-lists/{priceList}/candidates', [PriceListController::class, 'candidates'])->name('price-lists.candidates');
Route::post('price-lists/{priceList}/bulk/preview', [PriceListController::class, 'previewBulk'])->name('price-lists.bulk.preview');
Route::post('price-lists/{priceList}/bulk', [PriceListController::class, 'applyBulk'])->name('price-lists.bulk');
Route::get('price-lists/{priceList}/export', [PriceListController::class, 'export'])->name('price-lists.export');
Route::post('price-lists/{priceList}/import', [PriceListController::class, 'import'])->name('price-lists.import');
Route::post('price-lists/{priceList}/remove', [PriceListController::class, 'removePrices'])->name('price-lists.remove');
Route::get('history', [PriceHistoryController::class, 'index'])->name('history');
Route::post('base-prices/bulk', [PriceListController::class, 'bulkBasePrice'])->name('base-prices.bulk');
Route::post('base-prices/bulk/preview', [PriceListController::class, 'previewBulkBasePrice'])->name('base-prices.bulk.preview');
Route::put('base-prices/{productId}', [PriceListController::class, 'setProductBasePrice'])->whereNumber('productId')->name('base-prices.product');
