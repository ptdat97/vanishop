<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Support\StoreLocale;
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
use Modules\Storefront\Http\LocalizedPaths;
use Modules\Storefront\Http\Middleware\PublicPageCache;

/*
| Native storefront (SSR, ADR-025). Đoạn đầu đường dẫn nằm trong vanishop.reserved_paths (Admin không được trùng).
| Đăng ký một lần cho mỗi ngôn ngữ (ADR-038): mặc định không tiền tố (`storefront.*`), ngôn ngữ khác dưới `/<locale>`
| (`storefront.<locale>.*`); đường dẫn theo bảng LocalizedPaths.
*/

foreach (StoreLocale::supported() as $locale) {
    $p = fn (string $key): string => LocalizedPaths::path($key, $locale);

    Route::middleware(['vani.storefront-context:'.$locale, 'vani.customer-session', 'vani.theme', 'vani.maintenance'])
        ->prefix(LocalizedPaths::prefix($locale))
        ->name(LocalizedPaths::namePrefix($locale))
        ->group(function () use ($locale, $p): void {
            // Trang công khai: không phiên, CDN cache được (storefront §5); phần riêng của khách qua GET /_vani/phien.
            Route::withoutMiddleware(PublicPageCache::SESSIONLESS)->middleware('vani.page-cache')->group(function () use ($p): void {
                Route::get($p('home'), HomeController::class)->name('home');
                Route::get($p('category'), [CatalogController::class, 'category'])->name('category');
                Route::get($p('brands'), [CatalogController::class, 'brands'])->name('brands');
                Route::get($p('brand'), [CatalogController::class, 'brand'])->name('brand');
                Route::get($p('search'), [CatalogController::class, 'search'])->name('search');
                Route::get($p('product'), [ProductController::class, 'show'])->name('product');
            });
            if ($locale === StoreLocale::default()) {
                Route::get('/_vani/phien', SessionController::class)->name('session');
            }

            // Ẩn giá với khách chưa đăng nhập (`storefront.price_visibility`): giỏ và checkout chuyển sang đăng nhập.
            Route::middleware('vani.prices-visible')->group(function () use ($p): void {
                Route::get($p('cart'), [CartController::class, 'show'])->name('cart');
                Route::middleware('throttle:vani-cart-create')->post($p('cart'), [CartController::class, 'add'])->name('cart.add');
                Route::post($p('cart.update'), [CartController::class, 'update'])->whereNumber('line')->name('cart.update');
                Route::post($p('cart.remove'), [CartController::class, 'remove'])->whereNumber('line')->name('cart.remove');
                Route::post($p('cart.select'), [CartController::class, 'select'])->name('cart.select');
                Route::middleware('throttle:vani-checkout')->post($p('cart.voucher'), [CartController::class, 'voucher'])->name('cart.voucher');

                Route::get($p('checkout'), [CheckoutController::class, 'show'])->middleware('vani.checkout-sign-in')->name('checkout');
                Route::middleware(['vani.checkout-sign-in', 'throttle:vani-checkout'])->post($p('checkout'), [CheckoutController::class, 'store'])->name('checkout.store');
            });

            Route::get($p('order'), [OrderController::class, 'show'])->name('order');
            Route::middleware('throttle:10,1')->post($p('order.pay'), [OrderController::class, 'pay'])->name('order.pay');
            Route::middleware('throttle:10,1')->post($p('order.received'), [OrderController::class, 'received'])->name('order.received');
            // Đổi/trả (khách): quyền theo token đơn trong phiên hoặc tài khoản sở hữu đơn.
            Route::middleware('throttle:10,1')->group(function () use ($p): void {
                Route::post($p('order.returns.store'), [ReturnController::class, 'store'])->name('order.returns.store');
                Route::post($p('order.returns.cancel'), [ReturnController::class, 'cancel'])->name('order.returns.cancel');
                Route::post($p('order.returns.shipped'), [ReturnController::class, 'shipped'])->name('order.returns.shipped');
            });

            Route::get($p('track'), [TrackOrderController::class, 'show'])->name('track');
            Route::middleware('throttle:vani-order-track')->post($p('track'), [TrackOrderController::class, 'search'])->name('track.search');

            Route::get($p('account.login'), [AccountController::class, 'login'])->name('account.login');
            Route::middleware('throttle:vani-customer-auth')->group(function () use ($p): void {
                Route::post($p('account.otp'), [AccountController::class, 'requestOtp'])->name('account.otp');
                Route::post($p('account.login'), [AccountController::class, 'verify'])->name('account.verify');
            });
            Route::middleware('vani.customer-session:required')->group(function () use ($p): void {
                Route::get($p('account'), [AccountController::class, 'dashboard'])->name('account');
                Route::get($p('account.orders'), [AccountController::class, 'orders'])->name('account.orders');
                Route::get($p('account.order'), [AccountController::class, 'order'])->name('account.order');
                Route::post($p('account.order.cancel'), [AccountController::class, 'cancelOrder'])->name('account.order.cancel');
                Route::get($p('account.addresses'), [AccountController::class, 'addresses'])->name('account.addresses');
                Route::get($p('account.profile'), [AccountSettingsController::class, 'profile'])->name('account.profile');
                Route::put($p('account.profile'), [AccountSettingsController::class, 'updateProfile'])->name('account.profile.update');
                Route::put($p('account.password'), [AccountSettingsController::class, 'updatePassword'])->middleware('throttle:vani-customer-auth')->name('account.password.update');
                Route::get($p('account.delete'), [AccountSettingsController::class, 'deletion'])->name('account.delete');
                Route::post($p('account.delete.otp'), [AccountSettingsController::class, 'requestDeletionOtp'])->middleware('throttle:vani-customer-auth')->name('account.delete.otp');
                Route::post($p('account.delete'), [AccountSettingsController::class, 'destroyAccount'])->middleware('throttle:vani-customer-auth')->name('account.delete.confirm');
                Route::get($p('account.addresses.create'), [AccountSettingsController::class, 'createAddress'])->name('account.addresses.create');
                Route::post($p('account.addresses'), [AccountSettingsController::class, 'storeAddress'])->name('account.addresses.store');
                Route::get($p('account.addresses.edit'), [AccountSettingsController::class, 'editAddress'])->whereNumber('address')->name('account.addresses.edit');
                Route::put($p('account.addresses.item'), [AccountSettingsController::class, 'updateAddress'])->whereNumber('address')->name('account.addresses.update');
                Route::post($p('account.addresses.default'), [AccountSettingsController::class, 'defaultAddress'])->whereNumber('address')->name('account.addresses.default');
                Route::delete($p('account.addresses.item'), [AccountSettingsController::class, 'destroyAddress'])->whereNumber('address')->name('account.addresses.destroy');
                Route::post($p('account.logout'), [AccountController::class, 'logout'])->name('account.logout');
            });
        });
}

// SEO: không cần phiên/theme; không chặn khi bảo trì (crawler vẫn đọc được robots/sitemap).
Route::middleware('vani.storefront-context')->group(function (): void {
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('storefront.robots');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('storefront.sitemap');
    Route::get('/sitemap/{page}.xml', [SeoController::class, 'sitemapPage'])->whereNumber('page')->name('storefront.sitemap.page');
});
