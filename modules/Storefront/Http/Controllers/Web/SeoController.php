<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Contracts\SitemapProvider;

/**
 * robots.txt và sitemap.xml của native storefront (storefront §5): trang chủ, danh mục, thương hiệu, sản phẩm đang hiển
 * thị và URL plugin cung cấp (SitemapProvider). Quá PER_FILE URL (giới hạn 50 000 của giao thức sitemap) → `/sitemap.xml`
 * là sitemap index trỏ tới `/sitemap/{n}.xml` (§3.20). Cache 1 giờ. Đường dẫn Admin không được nhắc tới (bí mật, ADR-020).
 */
final class SeoController
{
    /** URL mỗi file sitemap (dưới giới hạn 50 000 của giao thức). */
    public const PER_FILE = 45_000;

    /** Trần tổng số URL (20 file) — chặn dựng sitemap vô hạn. */
    private const MAX_URLS = self::PER_FILE * 20;

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

    public function sitemap(CatalogReader $catalog, Extensions $extensions): Response
    {
        $files = $this->files($catalog, $extensions);
        if (count($files) === 1) {
            return $this->xml($files[0]);
        }

        $body = '';
        foreach (array_keys($files) as $index) {
            $body .= '<sitemap><loc>'.htmlspecialchars(route('storefront.sitemap.page', ['page' => $index + 1]), ENT_XML1).'</loc></sitemap>';
        }

        return $this->xml('<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</sitemapindex>'."\n");
    }

    public function sitemapPage(int $page, CatalogReader $catalog, Extensions $extensions): Response
    {
        $files = $this->files($catalog, $extensions);
        abort_unless(count($files) > 1 && isset($files[$page - 1]), 404);

        return $this->xml($files[$page - 1]);
    }

    /**
     * @return list<string> XML từng file sitemap
     */
    private function files(CatalogReader $catalog, Extensions $extensions): array
    {
        return Cache::remember('vani:sitemap:files', 3600, fn (): array => $this->build($catalog, $extensions));
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Sitemap đa ngôn ngữ (ADR-038 §6): mỗi bản đã dịch là một `<url>`, kèm `xhtml:link` tới mọi bản đã dịch và
     * `x-default` (ngôn ngữ mặc định). Bản chưa dịch không vào sitemap. URL của plugin (`SitemapProvider`) giữ nguyên.
     *
     * @return list<string>
     */
    private function build(CatalogReader $catalog, Extensions $extensions): array
    {
        $locales = StoreLocale::supported();
        $default = StoreLocale::default();
        /** @var list<array<string, string>> $groups mỗi phần tử: mã ngôn ngữ => URL của cùng nội dung (chỉ bản đã dịch) */
        $groups = [];
        $all = fn (string $route, mixed $parameters = []): array => array_combine($locales, array_map(fn (string $locale): string => LocalizedUrls::route($route, $parameters, $locale), $locales));

        $groups[] = $all('storefront.home');
        $groups[] = $all('storefront.brands');
        $walk = function (array $nodes) use (&$walk, &$groups): void {
            foreach ($nodes as $node) {
                $groups[] = array_intersect_key(PageAlternates::forSlugs('storefront.category', $node['slugs']), array_flip($node['locales']));
                $walk($node['children'] ?? []);
            }
        };
        $walk($catalog->categoryTree($default));
        foreach ($catalog->brands() as $brand) {
            $groups[] = $all('storefront.brand', $brand['slug']);
        }

        $now = now()->getTimestamp();
        $products = [];
        foreach ($locales as $locale) {
            for ($page = 1; count($products) < self::MAX_URLS; $page++) {
                $items = $catalog->searchProducts(new ProductFilters(now: $now, page: $page, perPage: ProductSearchQuery::MAX_PER_PAGE), $locale)['items'];
                foreach ($items as $item) {
                    if (in_array($locale, (array) ($item['locales'] ?? [$default]), true)) {
                        $products[$item['id']][$locale] = LocalizedUrls::route('storefront.product', $item['slug'], $locale);
                    }
                }
                if (count($items) < ProductSearchQuery::MAX_PER_PAGE) {
                    break;
                }
            }
        }
        $groups = [...$groups, ...array_values($products)];

        // URL của plugin (trang nội dung, bài viết…): lỗi của một plugin chỉ bỏ phần của plugin đó.
        foreach ($extensions->tagged(SitemapProvider::TAG) as $provider) {
            if ($provider instanceof SitemapProvider && count($groups) < self::MAX_URLS) {
                $limit = self::MAX_URLS - count($groups);
                foreach (array_slice($extensions->call($provider, fn (): array => $provider->urls($limit), [], 'sitemap'), 0, $limit) as $url) {
                    $groups[] = [$default => (string) $url];
                }
            }
        }

        $entries = [];
        $seen = [];
        foreach ($groups as $group) {
            $links = '';
            if (count($group) > 1) {
                foreach ([...$group, ...(isset($group[$default]) ? ['x-default' => $group[$default]] : [])] as $hreflang => $href) {
                    $links .= '<xhtml:link rel="alternate" hreflang="'.$hreflang.'" href="'.htmlspecialchars($href, ENT_XML1).'"/>';
                }
            }
            foreach ($group as $url) {
                if (! isset($seen[$url])) {
                    $seen[$url] = true;
                    $entries[] = '<url><loc>'.htmlspecialchars($url, ENT_XML1).'</loc>'.$links.'</url>';
                }
            }
        }

        return array_map(
            fn (array $chunk): string => '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'.implode('', $chunk).'</urlset>'."\n",
            array_chunk($entries, self::PER_FILE) ?: [[]],
        );
    }
}
