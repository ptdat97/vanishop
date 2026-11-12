<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Api\AccountOrderController;
use Modules\Storefront\Http\Controllers\Api\AddressController;
use Modules\Storefront\Http\Controllers\Api\BrandController;
use Modules\Storefront\Http\Controllers\Api\CartController;
use Modules\Storefront\Http\Controllers\Api\CategoryController;
use Modules\Storefront\Http\Controllers\Api\CheckoutController;
use Modules\Storefront\Http\Controllers\Api\OrderController;
use Modules\Storefront\Http\Controllers\Api\PaymentController;
use Modules\Storefront\Http\Controllers\Api\ProductController;
use Modules\Storefront\Http\Controllers\Api\ProductViewController;
use Modules\Storefront\Http\Controllers\Api\StoreController;

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('brands/{slug}', [BrandController::class, 'show'])->name('brands.show');
// Khách đăng nhập (Bearer) thấy giá khi cửa hàng ẩn giá với khách chưa đăng nhập (`storefront.price_visibility`).
Route::middleware('vani.customer:optional')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    // Beacon lượt xem PDP (miễn CSRF — chỉ cộng lượt xem, đã lọc trùng; StorefrontServiceProvider).
    Route::post('products/views', ProductViewController::class)->middleware('throttle:120,1')->name('products.views');
    Route::get('search/suggestions', [ProductController::class, 'suggestions'])->name('search.suggestions');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
});
// "Vỏ" trang (ADR-044 đợt A2): thông tin cửa hàng, menu theo vị trí, trang chủ dựng bằng khối.
Route::middleware('vani.customer:optional')->group(function () {
    Route::get('store', [StoreController::class, 'show'])->name('store.show');
    Route::get('menus/{location}', [StoreController::class, 'menu'])->where('location', '[a-z0-9_]{1,32}')->name('menus.show');
    Route::get('pages/home', [StoreController::class, 'home'])->name('pages.home');
});
Route::get('address/provinces', [AddressController::class, 'provinces'])->name('address.provinces');
Route::get('address/provinces/{province}/wards', [AddressController::class, 'wards'])->where('province', '[0-9A-Za-z_-]{1,16}')->name('address.wards');

// Giỏ & checkout: dùng được khi chưa đăng nhập (token giỏ) hoặc đã đăng nhập (Bearer, giỏ của khách).
Route::middleware('vani.customer:optional')->group(function () {
    Route::post('carts', [CartController::class, 'store'])->middleware('throttle:vani-cart-create')->name('carts.store');
    // Ẩn giá với khách chưa đăng nhập: giỏ (có giá) và checkout → 403 storefront.sign_in_to_view_prices. Tạo giỏ rỗng vẫn được.
    Route::middleware('vani.prices-visible')->group(function () {
        Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
        Route::post('carts/{cart}/lines', [CartController::class, 'addLine'])->name('carts.lines.store');
        Route::patch('carts/{cart}/lines/{line}', [CartController::class, 'updateLine'])->whereNumber('line')->name('carts.lines.update');
        Route::delete('carts/{cart}/lines/{line}', [CartController::class, 'removeLine'])->whereNumber('line')->name('carts.lines.destroy');
        Route::put('carts/{cart}/selection', [CartController::class, 'selection'])->name('carts.selection');
        Route::put('carts/{cart}/vouchers', [CartController::class, 'vouchers'])->middleware('throttle:vani-voucher')->name('carts.vouchers');

        Route::post('checkout/{cart}/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
        Route::post('checkout/{cart}/orders', [CheckoutController::class, 'placeOrder'])->middleware('throttle:vani-checkout')->name('checkout.orders.store');
    });
});

Route::get('payments/{payment}', [PaymentController::class, 'show'])->where('payment', '[0-9A-Z]{26}')->name('payments.show');

Route::get('orders/track', [OrderController::class, 'track'])->middleware('throttle:vani-order-track')->name('orders.track');
Route::get('orders/{order}', [OrderController::class, 'show'])->where('order', '[0-9A-Z]{26}')->name('orders.show');
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->where('order', '[0-9A-Z]{26}')->name('orders.cancel');
Route::post('orders/{order}/returns', [OrderController::class, 'requestReturn'])->where('order', '[0-9A-Z]{26}')->middleware('throttle:vani-checkout')->name('orders.returns.store');
Route::post('orders/{order}/returns/{return}/cancel', [OrderController::class, 'cancelReturn'])->where(['order' => '[0-9A-Z]{26}', 'return' => '[0-9A-Z]{26}'])->name('orders.returns.cancel');
Route::post('orders/{order}/received', [OrderController::class, 'confirmReceived'])->where('order', '[0-9A-Z]{26}')->middleware('throttle:10,1')->name('orders.received');
Route::post('orders/{order}/payment/retry', [OrderController::class, 'retryPayment'])->where('order', '[0-9A-Z]{26}')->middleware('throttle:10,1')->name('orders.payment.retry');
Route::post('orders/{order}/returns/{return}/shipped', [OrderController::class, 'markReturnShipped'])->where(['order' => '[0-9A-Z]{26}', 'return' => '[0-9A-Z]{26}'])->middleware('throttle:10,1')->name('orders.returns.shipped');

Route::middleware('vani.customer')->group(function () {
    Route::get('me/cart', [AccountOrderController::class, 'cart'])->name('me.cart');
    // Giá thành viên theo nhóm khách (Phase 8): ?variant_ids[]=…; chỉ variant có giá thấp hơn giá chung.
    Route::get('me/prices', [AccountOrderController::class, 'prices'])->name('me.prices');
    Route::get('me/orders', [AccountOrderController::class, 'index'])->name('me.orders.index');
    Route::get('me/orders/{order}', [AccountOrderController::class, 'show'])->where('order', '[0-9A-Z]{26}')->name('me.orders.show');
    Route::post('me/orders/{order}/cancel', [AccountOrderController::class, 'cancel'])->where('order', '[0-9A-Z]{26}')->name('me.orders.cancel');
});
