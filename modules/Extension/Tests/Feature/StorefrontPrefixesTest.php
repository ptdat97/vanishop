<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Modules\Extension\Application\Storefront\StorefrontPrefixes;
use Modules\Extension\Contracts\Extensions;
use Modules\Storefront\Contracts\SitemapProvider;

it('prefix storefront của plugin: không trùng Core, plugin khác, sai định dạng thì bị từ chối', function () {
    $prefixes = new StorefrontPrefixes;
    $prefixes->claim('tin-tuc', 'vani.a');
    $prefixes->claim('tin-tuc', 'vani.a'); // cùng plugin gọi lại: được

    expect(fn () => $prefixes->claim('tin-tuc', 'vani.b'))->toThrow(InvalidArgumentException::class, 'đã thuộc plugin [vani.a]')
        ->and(fn () => $prefixes->claim('san-pham', 'vani.b'))->toThrow(InvalidArgumentException::class, 'Core')
        ->and(fn () => $prefixes->claim('admin', 'vani.b'))->toThrow(InvalidArgumentException::class, 'Core')
        ->and(fn () => $prefixes->claim('Tin_Tuc', 'vani.b'))->toThrow(InvalidArgumentException::class, 'không hợp lệ')
        ->and($prefixes->all())->toBe(['tin-tuc' => 'vani.a']);
});

it('danh sách giữ chỗ bao phủ đoạn đầu mọi route storefront của Core', function () {
    $segments = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'storefront.') && ! str_starts_with((string) $route->getName(), 'storefront.p.'))
        ->map(fn (Route $route): string => explode('/', trim($route->uri(), '/'))[0])
        ->reject(fn (string $segment): bool => $segment === '')
        ->unique()->values()->all();

    expect(array_diff($segments, StorefrontPrefixes::RESERVED))->toBe([]);
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
