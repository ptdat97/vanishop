<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

/**
 * schema.org JSON-LD cho trang storefront (SEO — storefront §5). Trả chuỗi JSON an toàn để in trong <script>.
 */
final class StructuredData
{
    /**
     * @param  array<string, mixed>  $product  ProductViews::detail
     */
    public static function product(array $product): string
    {
        return self::encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'],
            'sku' => $product['product_code'],
            'image' => $product['image_url'],
            'description' => $product['description'],
            'brand' => $product['brand'] === null ? null : ['@type' => 'Brand', 'name' => $product['brand']['name']],
            'offers' => $product['price'] === null ? null : [
                '@type' => 'AggregateOffer',
                'priceCurrency' => $product['price']['min']['currency'],
                'lowPrice' => $product['price']['min']['amount'],
                'highPrice' => $product['price']['max']['amount'],
                'availability' => $product['in_stock'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * `BreadcrumbList` (0.3.66): mục không có URL (trang hiện tại) bỏ `item` như schema.org cho phép.
     *
     * @param  list<array{label: string, url: string|null}>  $crumbs
     */
    public static function breadcrumbs(array $crumbs): string
    {
        return self::encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (int $index, array $crumb): array => array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['label'],
                'item' => $crumb['url'],
            ], fn (mixed $value): bool => $value !== null), array_keys($crumbs), $crumbs),
        ]);
    }

    /**
     * Trang chủ (0.5.25): `Organization` (tên, logo = ảnh chia sẻ, SĐT, mạng xã hội) + `WebSite` có `SearchAction` (ô tìm
     * kiếm trên Google), chung một `@graph`.
     *
     * @param  list<string>  $sameAs  link mạng xã hội của cửa hàng
     */
    public static function store(string $name, string $url, string $searchUrl, ?string $logo, ?string $phone, array $sameAs): string
    {
        return self::encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'Organization',
                    '@id' => "{$url}#organization",
                    'name' => $name,
                    'url' => $url,
                    'logo' => $logo,
                    'contactPoint' => $phone === null ? null : ['@type' => 'ContactPoint', 'telephone' => $phone, 'contactType' => 'customer service'],
                    'sameAs' => $sameAs === [] ? null : $sameAs,
                ], fn (mixed $value): bool => $value !== null),
                [
                    '@type' => 'WebSite',
                    'name' => $name,
                    'url' => $url,
                    'publisher' => ['@id' => "{$url}#organization"],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => "{$searchUrl}?q={search_term_string}"],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function encode(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    }
}
