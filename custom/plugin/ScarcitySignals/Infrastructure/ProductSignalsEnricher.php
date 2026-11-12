<?php

declare(strict_types=1);

namespace Plugin\ScarcitySignals\Infrastructure;

use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Trang sản phẩm (native + Storefront API): `extensions["vani.scarcity-signals"]` =
 * `{low_stock: {variant id: còn lại}, threshold, sold: {count, days}|null}`. Chỉ variant có 0 < còn lại ≤ ngưỡng mới có số.
 */
final class ProductSignalsEnricher implements StorefrontEnricher
{
    public function __construct(private readonly Signals $signals) {}

    public function resource(): string
    {
        return 'product';
    }

    public function enrich(array $items, string $locale): array
    {
        $result = [];
        foreach ($items as $product) {
            $threshold = $this->signals->threshold(array_values(array_filter((array) ($product['breadcrumb'] ?? []), 'is_array')));
            $lowStock = (bool) $this->signals->setting('show_low_stock', true)
                ? $this->signals->lowStock(array_map('intval', array_column((array) ($product['variants'] ?? []), 'id')), $threshold)
                : [];
            $sold = $this->signals->sold((int) $product['id']);
            if ($lowStock !== [] || $sold !== null) {
                $result[$product['id']] = ['low_stock' => (object) $lowStock, 'threshold' => $threshold, 'sold' => $sold];
            }
        }

        return $result;
    }
}
