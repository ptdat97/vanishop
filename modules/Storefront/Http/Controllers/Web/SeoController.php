<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;

/**
 * robots.txt và sitemap.xml của native storefront (storefront §5): một sitemap gồm trang chủ, danh mục, thương hiệu,
 * sản phẩm đang hiển thị. Cache 1 giờ. Đường dẫn Admin không được nhắc tới (bí mật, ADR-020).
 */
final class SeoController
{
    private const MAX_URLS = 45_000;

    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        foreach (['/tai-khoan', '/gio-hang', '/thanh-toan', '/don-hang', '/tra-cuu-don', '/api/'] as $path) {
            $lines[] = "Disallow: {$path}";
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(CatalogReader $catalog): Response
    {
        $xml = Cache::remember('vani:sitemap:'.App::getLocale(), 3600, fn (): string => $this->build($catalog));

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(CatalogReader $catalog): string
    {
        $urls = [url('/'), route('storefront.brands')];
        $walk = function (array $nodes) use (&$walk, &$urls): void {
            foreach ($nodes as $node) {
                $urls[] = route('storefront.category', $node['slug']);
                $walk($node['children'] ?? []);
            }
        };
        $walk($catalog->categoryTree(App::getLocale()));
        foreach ($catalog->brands() as $brand) {
            $urls[] = route('storefront.brand', $brand['slug']);
        }

        $now = now()->getTimestamp();
        for ($page = 1; count($urls) < self::MAX_URLS; $page++) {
            $items = $catalog->searchProducts(new ProductFilters(now: $now, page: $page, perPage: ProductSearchQuery::MAX_PER_PAGE), App::getLocale())['items'];
            foreach ($items as $item) {
                $urls[] = route('storefront.product', $item['slug']);
            }
            if (count($items) < ProductSearchQuery::MAX_PER_PAGE) {
                break;
            }
        }

        $body = implode('', array_map(fn (string $url): string => '<url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc></url>', array_unique($urls)));

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>'."\n";
    }
}
