<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../modules/Customer/Tests/Feature/CustomerTestHelpers.php';

it('cập nhật giá, tài khoản và số món sau khi khách đăng nhập', function (bool $cachePages) {
    config(['vanishop.storefront.page_cache.mode' => $cachePages ? 'app' : 'off']);
    T::seed(fn () => app(Settings::class)->set('core', 'storefront.price_visibility', 'members'));
    ['s' => $variant] = C::store();
    $customer = H::account('0912345678');

    $page = visit('/')
        ->assertScript('document.documentElement.hasAttribute("data-vani-session")', $cachePages)
        ->assertAttributeContains('header [data-vani-account]', 'href', '/tai-khoan/dang-nhap')
        ->assertMissing('header [data-vani-cart-count]')
        ->assertVisible('[data-vani-price-for]')
        ->assertNoJavaScriptErrors();

    $page->navigate('/tai-khoan/dang-nhap')
        ->fill('email', $customer->email)->fill('password', H::PASSWORD)
        ->click('form[data-vani-login] button[type="submit"]')
        ->assertPathIs('/tai-khoan')
        ->navigate("/san-pham/{$variant->product->slug}?sku={$variant->sku}")
        ->waitForText('300.000')
        ->click('form[data-variant-form] button[type="submit"]:not([name])')
        ->assertVisible('dialog[data-vani-offcanvas-cart][open]')
        ->assertSeeIn('[data-vani-mini-cart-body]', 'Đầm lụa')
        ->navigate('/')
        ->assertSeeIn('header [data-vani-cart-count]', '1')
        ->assertAttributeContains('header [data-vani-account]', 'href', '/tai-khoan')
        ->assertAttributeDoesntContain('header [data-vani-account]', 'href', '/dang-nhap')
        ->assertScript('window.vaniSession.signed_in', true)
        ->assertNoJavaScriptErrors();
})->with(['phiên nhúng' => false, 'phiên tải qua JSON' => true]);

it('ẩn giá với khách chưa đăng nhập: vùng DOM thay sau (lọc danh sách, giỏ mini, plugin) cũng được điền giá cho khách đã đăng nhập', function () {
    config(['vanishop.storefront.page_cache.mode' => 'app']);
    T::seed(fn () => app(Settings::class)->set('core', 'storefront.price_visibility', 'members'));
    ['s' => $variant] = C::store();
    $customer = H::account('0912345678');
    $productId = $variant->product_id;

    $page = visit('/tai-khoan/dang-nhap')
        ->fill('email', $customer->email)->fill('password', H::PASSWORD)
        ->click('form[data-vani-login] button[type="submit"]')
        ->assertPathIs('/tai-khoan')
        ->navigate('/')
        ->assertScript('window.vaniSession.signed_in', true);

    $page->script(<<<JS
        () => {
            const box = document.createElement('div');
            box.id = 'vani-test-replaced';
            box.innerHTML = '<span data-vani-price-for="{$productId}">Đăng nhập để xem giá</span>';
            document.body.append(box);
            document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: box } }));
        }
    JS);
    $page->waitForText('200.000')
        ->assertSeeIn('#vani-test-replaced', '300.000')
        ->assertMissing('#vani-test-replaced [data-vani-price-for]')
        ->assertNoJavaScriptErrors();
});

it('trang khôi phục từ back/forward cache lấy lại phiên: số món mới; /_vani/phien lỗi thì thử lại, gọi cùng lúc chỉ một request', function () {
    config(['vanishop.storefront.page_cache.mode' => 'app']);
    ['s' => $variant] = C::store();

    $page = visit('/')->assertMissing('header [data-vani-cart-count]')->assertNoJavaScriptErrors();
    // Tab khác thêm vào giỏ (cùng phiên), rồi khách bấm Back về trang cũ.
    $page->script(<<<JS
        async () => {
            await fetch('/gio-hang', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ variant_id: {$variant->id}, quantity: 2 }) });
            const original = window.fetch;
            window.sessionCalls = 0;
            window.fetch = (url, options) => {
                if (String(url).includes('/_vani/phien')) {
                    window.sessionCalls++;
                    if (window.sessionCalls === 1) return Promise.reject(new TypeError('mạng lỗi'));
                }
                return original(url, options);
            };
            window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true }));
            window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true }));
        }
    JS);
    $page->assertSeeIn('header [data-vani-cart-count]', '2')
        ->assertScript('window.sessionCalls', 2)
        ->assertNoJavaScriptErrors();
});

it('Escape giữ hộp gợi ý ẩn khi phản hồi đến chậm, đóng modal trả focus về nút tìm kiếm', function () {
    C::store();
    $opener = '[data-vani-search-open]';
    $input = '#vani-search-modal-q';
    $box = '#vani-search-modal [data-vani-suggest-box]';
    $page = visit('/')->resize(375, 812)->click($opener)
        ->fill($input, 'lua')
        ->assertSeeIn($box, 'Đầm lụa');

    $page->script(<<<'JS'
        () => {
            const fetchOriginal = window.fetch;
            window.delayedSuggestionReady = new Promise((ready) => {
                window.fetch = async (url, options) => {
                    if (new URL(url, location.href).searchParams.get('q') !== 'dam') {
                        return fetchOriginal(url, options);
                    }
                    const response = await fetchOriginal(url, { ...options, signal: undefined });
                    const data = await response.json();
                    return new Promise((resolve) => {
                        window.finishDelayedSuggestion = () => resolve({ ok: response.ok, json: async () => data });
                        ready(true);
                    });
                };
            });
        }
    JS);
    $page->fill($input, 'dam')
        ->assertScript('window.delayedSuggestionReady', true)
        ->keys($input, 'Escape');
    $page->script('() => { window.finishDelayedSuggestion(); return new Promise(requestAnimationFrame); }');
    $page->assertScript("document.querySelector('{$box}').childElementCount", 0)
        ->assertAttribute($box, 'hidden', '')
        ->keys('#vani-search-modal [data-vani-modal-close]', 'Enter')
        ->assertMissing('#vani-search-modal')
        ->assertScript('document.activeElement.hasAttribute("data-vani-search-open")', true)
        ->assertNoJavaScriptErrors();
});
