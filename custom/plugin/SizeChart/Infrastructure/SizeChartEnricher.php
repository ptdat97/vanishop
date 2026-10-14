<?php

declare(strict_types=1);

namespace Plugin\SizeChart\Infrastructure;

use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Trang sản phẩm (native + Storefront API): `extensions["vani.size-chart"]` = bảng size áp dụng (khối, đơn vị, luật gợi ý).
 */
final class SizeChartEnricher implements StorefrontEnricher
{
    public function __construct(private readonly SizeCharts $charts) {}

    public function resource(): string
    {
        return 'product';
    }

    public function enrich(array $items, string $locale): array
    {
        $result = [];
        foreach ($items as $product) {
            $chart = $this->charts->resolve(
                (int) $product['id'],
                array_values(array_map(fn (array $crumb): int => (int) $crumb['id'], array_filter($product['breadcrumb'] ?? [], fn (array $crumb): bool => isset($crumb['id'])))),
                isset($product['brand']['id']) ? (int) $product['brand']['id'] : null,
            );
            if ($chart !== null) {
                $result[$product['id']] = $this->charts->present($chart, $locale);
            }
        }

        return $result;
    }
}
