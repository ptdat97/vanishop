<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\Web\AccountController;
use Modules\Storefront\Http\Controllers\Web\AccountSettingsController;
use Modules\Storefront\Http\Controllers\Web\CartController;
use Modules\Storefront\Http\Controllers\Web\CatalogController;
use Modules\Storefront\Http\Controllers\Web\CheckoutController;
use Modules\Storefront\Http\Controllers\Web\HomeController;
use Modules\Storefront\Http\Controllers\Web\OrderController;
use Modules\Storefront\Http\Controllers\Web\ProductController;
use Modules\Storefront\Http\Controllers\Web\ReturnController;
use Modules\Storefront\Http\Controllers\Web\SeoController;
use Modules\Storefront\Http\Controllers\Web\SessionController;
use Modules\Storefront\Http\Controllers\Web\TrackOrderController;
use Modules\Storefront\Http\Middleware\PublicPageCache;

/*
| Native storefront (SSR, ADR-025). Đoạn đầu đường dẫn nằm trong vanishop.reserved_paths (Admin không được trùng).
*/

Route::middleware(['vani.storefront-context', 'vani.customer-session', 'vani.theme'])->name('storefront.')->group(function (): void {
    // Trang công khai: không phiên, CDN cache được (storefront §5); phần riêng của khách qua GET /_vani/phien.
    Route::withoutMiddleware(PublicPageCache::SESSIONLESS)->middleware('vani.page-cache')->group(function (): void {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/danh-muc/{slug}', [CatalogController::class, 'category'])->name('category');
        Route::get('/thuong-hieu', [CatalogController::class, 'brands'])->name('brands');
        Route::get('/thuong-hieu/{slug}', [CatalogController::class, 'brand'])->name('brand');
        Route::get('/tim-kiem', [CatalogController::class, 'search'])->name('search');
        Route::get('/san-pham/{slug}', [ProductController::class, 'show'])->name('product');
    });
    Route::get('/_vani/phien', SessionController::class)->name('session');
    Route::get('/_vani/gia', [SessionController::class, 'prices'])->name('session.prices');

    Route::get('/gio-hang', [CartController::class, 'show'])->name('cart');
    Route::middleware('throttle:vani-cart-create')->post('/gio-hang', [CartController::class, 'add'])->name('cart.add');
    Route::post('/gio-hang/{line}', [CartController::class, 'update'])->whereNumber('line')->name('cart.update');
    Route::post('/gio-hang/{line}/xoa', [CartController::class, 'remove'])->whereNumber('line')->name('cart.remove');

    Route::get('/thanh-toan', [CheckoutController::class, 'show'])->name('checkout');
    Route::middleware('throttle:vani-checkout')->post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/don-hang/{order}', [OrderController::class, 'show'])->name('order');
    // Đổi/trả (khách): quyền theo token đơn trong phiên hoặc tài khoản sở hữu đơn.
    Route::middleware('throttle:10,1')->group(function (): void {
        Route::post('/don-hang/{order}/doi-tra', [ReturnController::class, 'store'])->name('order.returns.store');
        Route::post('/don-hang/{order}/doi-tra/{return}/huy', [ReturnController::class, 'cancel'])->name('order.returns.cancel');
    });

    Route::get('/tra-cuu-don', [TrackOrderController::class, 'show'])->name('track');
    Route::middleware('throttle:vani-order-track')->post('/tra-cuu-don', [TrackOrderController::class, 'search'])->name('track.search');

    Route::get('/tai-khoan/dang-nhap', [AccountController::class, 'login'])->name('account.login');
    Route::middleware('throttle:vani-customer-auth')->group(function (): void {
        Route::post('/tai-khoan/dang-nhap/otp', [AccountController::class, 'requestOtp'])->name('account.otp');
        Route::post('/tai-khoan/dang-nhap', [AccountController::class, 'verify'])->name('account.verify');
    });
    Route::middleware('vani.customer-session:required')->group(function (): void {
        Route::get('/tai-khoan', [AccountController::class, 'dashboard'])->name('account');
        Route::get('/tai-khoan/don-hang', [AccountController::class, 'orders'])->name('account.orders');
        Route::get('/tai-khoan/don-hang/{order}', [AccountController::class, 'order'])->name('account.order');
        Route::post('/tai-khoan/don-hang/{order}/huy', [AccountController::class, 'cancelOrder'])->name('account.order.cancel');
        Route::get('/tai-khoan/dia-chi', [AccountController::class, 'addresses'])->name('account.addresses');
        Route::get('/tai-khoan/ho-so', [AccountSettingsController::class, 'profile'])->name('account.profile');
        Route::put('/tai-khoan/ho-so', [AccountSettingsController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('/tai-khoan/mat-khau', [AccountSettingsController::class, 'updatePassword'])->middleware('throttle:vani-customer-auth')->name('account.password.update');
        Route::get('/tai-khoan/dia-chi/them', [AccountSettingsController::class, 'createAddress'])->name('account.addresses.create');
        Route::post('/tai-khoan/dia-chi', [AccountSettingsController::class, 'storeAddress'])->name('account.addresses.store');
        Route::get('/tai-khoan/dia-chi/{address}/sua', [AccountSettingsController::class, 'editAddress'])->whereNumber('address')->name('account.addresses.edit');
        Route::put('/tai-khoan/dia-chi/{address}', [AccountSettingsController::class, 'updateAddress'])->whereNumber('address')->name('account.addresses.update');
        Route::post('/tai-khoan/dia-chi/{address}/mac-dinh', [AccountSettingsController::class, 'defaultAddress'])->whereNumber('address')->name('account.addresses.default');
        Route::delete('/tai-khoan/dia-chi/{address}', [AccountSettingsController::class, 'destroyAddress'])->whereNumber('address')->name('account.addresses.destroy');
        Route::post('/tai-khoan/dang-xuat', [AccountController::class, 'logout'])->name('account.logout');
    });
});

// SEO: không cần phiên/theme.
Route::middleware('vani.storefront-context')->group(function (): void {
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('storefront.robots');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('storefront.sitemap');
});
