<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Admin\AttributeController;
use Modules\Catalog\Http\Controllers\Admin\BrandController;
use Modules\Catalog\Http\Controllers\Admin\CategoryController;
use Modules\Catalog\Http\Controllers\Admin\CollectionController;
use Modules\Catalog\Http\Controllers\Admin\ColorController;
use Modules\Catalog\Http\Controllers\Admin\FacetController;
use Modules\Catalog\Http\Controllers\Admin\LinkTypeController;
use Modules\Catalog\Http\Controllers\Admin\ProductColorController;
use Modules\Catalog\Http\Controllers\Admin\ProductController;
use Modules\Catalog\Http\Controllers\Admin\ProductLinkController;
use Modules\Catalog\Http\Controllers\Admin\ProductVariantController;
use Modules\Catalog\Http\Controllers\Admin\ProductVideoController;
use Modules\Catalog\Http\Controllers\Admin\SizeController;

// Prefix: /{admin}/catalog — tên route: admin.catalog.*
Route::resource('categories', CategoryController::class)->except('show');
Route::post('categories/{category}/image', [CategoryController::class, 'uploadImage'])->name('categories.image.store');
Route::post('categories/{category}/image/library', [CategoryController::class, 'attachLibraryImage'])->name('categories.image.library');
Route::delete('categories/{category}/image', [CategoryController::class, 'removeImage'])->name('categories.image.destroy');

Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('attributes', AttributeController::class)->except('show');
Route::resource('colors', ColorController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('sizes', SizeController::class)->only(['index', 'store', 'update', 'destroy']);
Route::get('filters', [FacetController::class, 'index'])->name('filters.index');
Route::put('filters', [FacetController::class, 'update'])->name('filters.update');
Route::resource('link-types', LinkTypeController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['link-types' => 'linkType']);

Route::resource('products', ProductController::class)->except('show');
Route::post('products/{product}/colors', [ProductColorController::class, 'store'])->name('products.colors.store');
Route::delete('products/{product}/colors/{styleColor}', [ProductColorController::class, 'destroy'])->name('products.colors.destroy');
Route::post('products/{product}/colors/{styleColor}/images', [ProductColorController::class, 'storeImages'])->name('products.colors.images.store');
Route::post('products/{product}/colors/{styleColor}/images/library', [ProductColorController::class, 'attachLibraryImages'])->name('products.colors.images.library');
Route::put('products/{product}/colors/{styleColor}/images/order', [ProductColorController::class, 'reorderImages'])->name('products.colors.images.order');
Route::delete('products/{product}/colors/{styleColor}/images/{image}', [ProductColorController::class, 'destroyImage'])->name('products.colors.images.destroy');

Route::post('products/{product}/variants/generate', [ProductVariantController::class, 'generate'])->name('products.variants.generate');
Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');

Route::post('products/{product}/links', [ProductLinkController::class, 'store'])->name('products.links.store');
Route::delete('products/{product}/links/{linkType}/{linked}', [ProductLinkController::class, 'destroy'])->name('products.links.destroy');

Route::post('products/{product}/videos', [ProductVideoController::class, 'store'])->name('products.videos.store');
Route::put('products/{product}/videos/order', [ProductVideoController::class, 'reorder'])->name('products.videos.order');
Route::delete('products/{product}/videos/{video}', [ProductVideoController::class, 'destroy'])->name('products.videos.destroy');

Route::resource('collections', CollectionController::class)->except('show');
