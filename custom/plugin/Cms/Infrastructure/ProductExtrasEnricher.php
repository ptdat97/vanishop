<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Trang sản phẩm (native + Storefront API): `extensions["vani.cms"]` = `highlights` (điểm nổi bật theo ngôn ngữ) và
 * `promo_box` (HTML hộp ưu đãi dùng chung; null khi tắt hoặc sản phẩm chọn ẩn).
 */
final class ProductExtrasEnricher implements StorefrontEnricher
{
    public function __construct(private readonly ExtraContent $content) {}

    public function resource(): string
    {
        return 'product';
    }

    public function enrich(array $items, string $locale): array
    {
        return $this->content->forProducts(array_map(fn (array $product): int => (int) $product['id'], $items), $locale);
    }
}
