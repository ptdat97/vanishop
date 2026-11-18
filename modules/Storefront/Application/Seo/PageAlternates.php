<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Seo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Contracts\PageSeo;
use Modules\Storefront\Http\Middleware\PublicPageCache;

/**
 * Thẻ SEO theo ngôn ngữ cho layout (ADR-038 §5–7):
 * - Trang công khai đã dịch: canonical tự trỏ, `hreflang` cho mọi ngôn ngữ đã dịch + `x-default` → ngôn ngữ mặc định.
 * - Trang công khai chưa dịch ở ngôn ngữ hiện hành: `noindex,follow`, canonical → bản ngôn ngữ mặc định, không hreflang.
 * - Trang giao dịch (không cache được: giỏ, checkout, đơn, tài khoản, tra cứu): `noindex`.
 * - URL canonical/hreflang chỉ giữ tham số `page` (> 1): bộ lọc, sắp xếp, `utm_*`… không tạo trang trùng (0.5.25).
 * Khai báo lưu trên request (attribute), service không giữ trạng thái.
 */
final class PageAlternates implements PageSeo
{
    private const ATTRIBUTE = 'vani.seo';

    public function __construct(private readonly Request $request) {}

    public function alternates(array $urls, array $translated): void
    {
        $this->request->attributes->set(self::ATTRIBUTE, [...$this->declared(), 'urls' => $urls, 'translated' => $translated]);
    }

    public function noindex(): void
    {
        $this->request->attributes->set(self::ATTRIBUTE, [...$this->declared(), 'noindex' => true]);
    }

    /**
     * @return array{canonical: ?string, robots: ?string, hreflang: array<string, string>, switcher: list<array{locale: string, label: string, url: string, current: bool}>}
     */
    public function tags(): array
    {
        $declared = $this->declared();
        $locale = App::getLocale();
        $default = StoreLocale::default();
        $routeUrls = $declared['urls'] ?? $this->fromRoute();
        $urls = array_map(fn (string $url): string => $this->withPage($url), $routeUrls);
        // Nút đổi ngôn ngữ giữ nguyên bộ lọc/sắp xếp khách đang xem (khác canonical).
        $query = $this->request->getQueryString();
        $switch = array_map(fn (string $url): string => $query !== null && $query !== '' ? "{$url}?{$query}" : $url, $routeUrls);
        $translated = $declared['translated'] ?? array_keys($urls);
        $public = (bool) $this->request->attributes->get(PublicPageCache::PUBLIC_PAGE) && ! ($declared['noindex'] ?? false);

        $switcher = [];
        foreach (StoreLocale::options() as $option) {
            $switcher[] = [
                'locale' => $option['code'],
                'label' => $option['label'],
                'url' => $switch[$option['code']] ?? LocalizedUrls::route('storefront.home', [], $option['code']),
                'current' => $option['code'] === $locale,
            ];
        }

        if (! $public) {
            return ['canonical' => null, 'robots' => 'noindex', 'hreflang' => [], 'switcher' => $switcher];
        }
        if (! in_array($locale, $translated, true)) {
            return ['canonical' => $urls[$default] ?? null, 'robots' => 'noindex,follow', 'hreflang' => [], 'switcher' => $switcher];
        }

        $hreflang = [];
        foreach ($translated as $code) {
            if (isset($urls[$code])) {
                $hreflang[$code] = $urls[$code];
            }
        }
        if (isset($urls[$default], $hreflang[$default])) {
            $hreflang['x-default'] = $urls[$default];
        }

        return ['canonical' => $urls[$locale] ?? null, 'robots' => null, 'hreflang' => count($hreflang) > 2 ? $hreflang : [], 'switcher' => $switcher];
    }

    /**
     * URL của một route có slug theo ngôn ngữ: `['vi' => 'dam-lua', 'en' => 'silk-dress']` → URL ở từng ngôn ngữ.
     *
     * @param  array<string, string>  $slugs
     * @return array<string, string>
     */
    public static function forSlugs(string $route, array $slugs): array
    {
        $urls = [];
        foreach ($slugs as $locale => $slug) {
            $urls[$locale] = LocalizedUrls::route($route, $slug, $locale);
        }

        return $urls;
    }

    /**
     * URL cùng route ở mỗi ngôn ngữ có route (tham số + query giữ nguyên) — cho trang không khai báo.
     *
     * @return array<string, string>
     */
    private function fromRoute(): array
    {
        $route = $this->request->route();
        $name = $route?->getName();
        if ($route === null || $name === null || ! str_starts_with(LocalizedUrls::baseName($name), 'storefront.')) {
            return [];
        }

        $base = LocalizedUrls::baseName($name);
        $urls = [];
        foreach (StoreLocale::supported() as $locale) {
            if (LocalizedUrls::has($base, $locale)) {
                $urls[$locale] = LocalizedUrls::route($base, $route->parameters(), $locale);
            }
        }

        return $urls;
    }

    /** Trang danh sách thứ N (> 1) tự canonical về chính nó (`?page=N`), không về trang 1. */
    private function withPage(string $url): string
    {
        $page = $this->request->query('page');
        $page = is_string($page) && ctype_digit($page) ? (int) $page : 1;

        return $page > 1 ? $url.(str_contains($url, '?') ? '&' : '?')."page={$page}" : $url;
    }

    /**
     * @return array{urls?: array<string, string>, translated?: list<string>, noindex?: bool}
     */
    private function declared(): array
    {
        return (array) $this->request->attributes->get(self::ATTRIBUTE, []);
    }
}
