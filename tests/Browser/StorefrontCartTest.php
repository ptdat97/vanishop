<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('bấm thêm hai lần chỉ thêm một món và phát một sự kiện, giỏ mini cho phép đổi số lượng', function () {
    ['s' => $variant] = C::store();
    $page = visit("/san-pham/{$variant->style->slug}?sku={$variant->sku}")->on()->mobile();
    $page->script(<<<'JS'
        () => {
            window.cartAdds = 0;
            document.addEventListener('vani:cart-updated', ({ detail }) => {
                if (detail.action === 'add') window.cartAdds++;
            });
            const form = document.querySelector('form[data-variant-form]');
            const add = form.querySelector('button[type=submit]:not([name])');
            form.requestSubmit(add);
            form.requestSubmit(add);
        }
    JS);
    $page->assertVisible('dialog[data-vani-offcanvas-cart][open]')
        ->assertSeeIn('header [data-vani-cart-count]', '1')
        ->assertScript('window.cartAdds', 1);
    $page->script(<<<'JS'
        () => {
            const originalFetch = window.fetch;
            let offline = false;
            window.fetch = async (url, options) => {
                if (options?.method === 'POST') offline = true;
                if (offline) {
                    if (url === window.vani.urls.cart && options?.method === 'GET') window.fetch = originalFetch;
                    throw new TypeError('Connection lost');
                }
                return originalFetch(url, options);
            };
        }
    JS);
    $page->click('[data-cart-quantity][data-quantity="2"]')
        ->assertSeeIn('[data-vani-cart-message]', 'Connection lost')
        ->click('[data-cart-quantity][data-quantity="2"]')
        ->assertSeeIn('header [data-vani-cart-count]', '2')
        ->navigate('/gio-hang')
        ->assertValue('input[data-autosubmit]', '2')
        ->assertNoJavaScriptErrors();
});

it('mất phản hồi sau khi máy chủ thêm hàng: mở giỏ với số lượng đã ghi đúng một lần', function () {
    ['s' => $variant] = C::store();
    $page = visit("/san-pham/{$variant->style->slug}?sku={$variant->sku}")->on()->mobile();
    $page->script(<<<'JS'
        () => {
            const originalFetch = window.fetch;
            window.fetch = async (url, options) => {
                const response = await originalFetch(url, options);
                if (url === window.vani.urls.cart_add && options?.method === 'POST' && response.ok) {
                    throw new TypeError('Connection lost');
                }
                return response;
            };
        }
    JS);
    $page->click('form[data-variant-form] button[type="submit"]:not([name])')
        ->assertPathIs('/gio-hang')
        ->assertValue('input[data-autosubmit]', '1')
        ->assertNoJavaScriptErrors();
});
