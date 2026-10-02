<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Web\CartController;
use Modules\Storefront\Http\Controllers\Web\CatalogController;
use Modules\Storefront\Http\Controllers\Web\CheckoutController;
use Modules\Storefront\Http\Controllers\Web\HomeController;
use Modules\Storefront\Http\Controllers\Web\OrderController;
use Modules\Storefront\Http\Controllers\Web\ProductController;

/*
| Native storefront (SSR, ADR-025). Đoạn đầu đường dẫn nằm trong vanishop.reserved_paths (Admin không được trùng).
*/

Route::middleware(['vani.storefront-context', 'vani.theme'])->name('storefront.')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/danh-muc/{slug}', [CatalogController::class, 'category'])->name('category');
    Route::get('/thuong-hieu', [CatalogController::class, 'brands'])->name('brands');
    Route::get('/thuong-hieu/{slug}', [CatalogController::class, 'brand'])->name('brand');
    Route::get('/tim-kiem', [CatalogController::class, 'search'])->name('search');
    Route::get('/san-pham/{slug}', [ProductController::class, 'show'])->name('product');

    Route::get('/gio-hang', [CartController::class, 'show'])->name('cart');
    Route::middleware('throttle:vani-cart-create')->post('/gio-hang', [CartController::class, 'add'])->name('cart.add');
    Route::post('/gio-hang/{line}', [CartController::class, 'update'])->whereNumber('line')->name('cart.update');
    Route::post('/gio-hang/{line}/xoa', [CartController::class, 'remove'])->whereNumber('line')->name('cart.remove');

    Route::get('/thanh-toan', [CheckoutController::class, 'show'])->name('checkout');
    Route::middleware('throttle:vani-checkout')->post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/don-hang/{order}', [OrderController::class, 'show'])->name('order');
});
