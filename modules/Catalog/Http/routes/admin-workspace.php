<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Admin\AttributeController;
use Modules\Catalog\Http\Controllers\Admin\BrandController;
use Modules\Catalog\Http\Controllers\Admin\CategoryController;
use Modules\Catalog\Http\Controllers\Admin\CollectionController;
use Modules\Catalog\Http\Controllers\Admin\FacetController;
use Modules\Catalog\Http\Controllers\Admin\LinkTypeController;
use Modules\Catalog\Http\Controllers\Admin\OptionTypeController;
use Modules\Catalog\Http\Controllers\Admin\ProductController;
use Modules\Catalog\Http\Controllers\Admin\ProductLinkController;
use Modules\Catalog\Http\Controllers\Admin\ProductVariantImageController;
use Modules\Catalog\Http\Controllers\Admin\ProductVideoController;

// Prefix: /{admin}/catalog — tên route: admin.catalog.*
Route::resource('categories', CategoryController::class)->except('show');
Route::post('categories/{category}/image', [CategoryController::class, 'uploadImage'])->name('categories.image.store');
Route::post('categories/{category}/image/library', [CategoryController::class, 'attachLibraryImage'])->name('categories.image.library');
Route::delete('categories/{category}/image', [CategoryController::class, 'removeImage'])->name('categories.image.destroy');

Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('attributes', AttributeController::class)->except('show');
// Tuỳ chọn biến thể (ADR-045): loại tuỳ chọn dùng chung + giá trị.
Route::resource('option-types', OptionTypeController::class)->parameters(['option-types' => 'optionType'])->only(['index', 'store', 'update', 'destroy']);
Route::post('option-types/{optionType}/values', [OptionTypeController::class, 'storeValue'])->name('option-types.values.store');
Route::put('option-types/{optionType}/values/{value}', [OptionTypeController::class, 'updateValue'])->name('option-types.values.update');
Route::delete('option-types/{optionType}/values/{value}', [OptionTypeController::class, 'destroyValue'])->name('option-types.values.destroy');
Route::get('filters', [FacetController::class, 'index'])->name('filters.index');
Route::put('filters', [FacetController::class, 'update'])->name('filters.update');
Route::resource('link-types', LinkTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['link-types' => 'linkType']);

Route::post('products/bulk-status', [ProductController::class, 'bulkStatus'])->name('products.bulk-status');
Route::resource('products', ProductController::class)->except('show');

Route::post('products/{product}/links', [ProductLinkController::class, 'store'])->name('products.links.store');
Route::delete('products/{product}/links/{linkType}/{linked}', [ProductLinkController::class, 'destroy'])->name('products.links.destroy');

// Ảnh theo SKU (ADR-045).
Route::post('products/{product}/variants/{variant}/images', [ProductVariantImageController::class, 'store'])->name('products.variants.images.store');
Route::post('products/{product}/variants/{variant}/images/library', [ProductVariantImageController::class, 'attach'])->name('products.variants.images.library');
Route::put('products/{product}/variants/{variant}/images/order', [ProductVariantImageController::class, 'reorder'])->name('products.variants.images.order');
Route::post('products/{product}/variants/{variant}/images/copy', [ProductVariantImageController::class, 'copy'])->name('products.variants.images.copy');
Route::delete('products/{product}/variants/{variant}/images/{image}', [ProductVariantImageController::class, 'destroy'])->name('products.variants.images.destroy');
Route::post('products/{product}/videos', [ProductVideoController::class, 'store'])->name('products.videos.store');
Route::put('products/{product}/videos/order', [ProductVideoController::class, 'reorder'])->name('products.videos.order');
Route::delete('products/{product}/videos/{video}', [ProductVideoController::class, 'destroy'])->name('products.videos.destroy');

Route::resource('collections', CollectionController::class)->except('show');
