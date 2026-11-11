<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Modules\Extension\Application\Storefront\StorefrontPrefixes;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Contracts\SitemapProvider;
use Modules\Storefront\Http\Controllers\Web\SeoController;

it('prefix storefront của plugin: không trùng Core, plugin khác, sai định dạng thì bị từ chối', function () {
    $prefixes = new StorefrontPrefixes;
    $prefixes->claim('tin-tuc', 'vani.a');
    $prefixes->claim('tin-tuc', 'vani.a'); // cùng plugin gọi lại: được

    expect(fn () => $prefixes->claim('tin-tuc', 'vani.b'))->toThrow(InvalidArgumentException::class, 'đã thuộc plugin [vani.a]')
        ->and(fn () => $prefixes->claim('san-pham', 'vani.b'))->toThrow(InvalidArgumentException::class, 'Core')
        ->and(fn () => $prefixes->claim('admin', 'vani.b'))->toThrow(InvalidArgumentException::class, 'Core')
        ->and(fn () => $prefixes->claim('Tin_Tuc', 'vani.b'))->toThrow(InvalidArgumentException::class, 'không hợp lệ')
        ->and($prefixes->all())->toBe(['vi:tin-tuc' => 'vani.a']);
});

it('danh sách giữ chỗ bao phủ đoạn đầu mọi route storefront của Core', function () {
    $segments = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'storefront.') && preg_match('/^storefront\.([a-z]{2}\.)?p\./', (string) $route->getName()) !== 1)
        // Route theo ngôn ngữ (/en/…, ADR-038): mã ngôn ngữ luôn bị giữ chỗ, xét đoạn ngay sau nó.
        ->map(function (Route $route): string {
            $segments = explode('/', trim($route->uri(), '/'));

            return in_array($segments[0], StoreLocale::supported(), true) ? ($segments[1] ?? '') : $segments[0];
        })
        ->reject(fn (string $segment): bool => $segment === '')
        ->unique()->values()->all();

    expect(array_diff($segments, (new StorefrontPrefixes)->reserved()))->toBe([]);
});

it('sitemap gộp URL của SitemapProvider; provider lỗi chỉ bị bỏ phần của nó', function () {
    app()->instance('test.sitemap.ok', new class implements SitemapProvider
    {
        public function urls(int $limit): array
        {
            return ['https://shop.test/trang/gioi-thieu'];
        }
    });
    app()->instance('test.sitemap.broken', new class implements SitemapProvider
    {
        public function urls(int $limit): array
        {
            throw new RuntimeException('hỏng');
        }
    });
    app(Extensions::class)->tag(['test.sitemap.broken', 'test.sitemap.ok'], SitemapProvider::TAG);
    Cache::flush();

    $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>https://shop.test/trang/gioi-thieu</loc>', false)->assertSee(url('/'));
});

it('đường dẫn trang plugin dưới tài khoản (accountRoutes): không trùng trang tài khoản của Core, plugin khác, sai định dạng', function () {
    $prefixes = new StorefrontPrefixes;
    $prefixes->claimAccount('yeu-thich', 'vani.a', 'vi');
    $prefixes->claimAccount('wishlist', 'vani.a', 'en');

    expect(fn () => $prefixes->claimAccount('yeu-thich', 'vani.b', 'vi'))->toThrow(InvalidArgumentException::class, 'đã thuộc plugin [vani.a]')
        ->and(fn () => $prefixes->claimAccount('don-hang', 'vani.b', 'vi'))->toThrow(InvalidArgumentException::class, 'Core')
        ->and(fn () => $prefixes->claimAccount('orders', 'vani.b', 'en'))->toThrow(InvalidArgumentException::class, 'Core')
        ->and(fn () => $prefixes->claimAccount('Yeu_Thich', 'vani.b', 'vi'))->toThrow(InvalidArgumentException::class, 'không hợp lệ')
        ->and($prefixes->all())->toBe(['account:vi:yeu-thich' => 'vani.a', 'account:en:wishlist' => 'vani.a']);
});

it('sitemap vượt một file → sitemap index + /sitemap/{n}.xml; một file thì không có trang con', function () {
    Cache::flush();
    $this->get('/sitemap/1.xml')->assertNotFound();

    app()->instance('test.sitemap.many', new class implements SitemapProvider
    {
        public function urls(int $limit): array
        {
            return array_map(fn (int $index): string => "https://shop.test/trang/{$index}", range(1, min($limit, SeoController::PER_FILE + 10)));
        }
    });
    app(Extensions::class)->tag(['test.sitemap.many'], SitemapProvider::TAG);
    Cache::flush();

    $this->get('/sitemap.xml')->assertOk()->assertSee('<sitemapindex', false)->assertSee(url('/sitemap/2.xml'), false);
    $this->get('/sitemap/2.xml')->assertOk()->assertSee('<urlset', false)->assertSee('https://shop.test/trang/'.(SeoController::PER_FILE + 10), false);
    $this->get('/sitemap/3.xml')->assertNotFound();
});
