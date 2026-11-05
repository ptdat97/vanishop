<?php

declare(strict_types=1);

namespace Plugin\ScarcitySignals\Infrastructure;

use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Thẻ sản phẩm: `extensions["vani.scarcity-signals"].low_stock = true` khi tổng số bán được của mọi variant còn > 0 và
 * ≤ ngưỡng chung (thẻ không có danh mục nên không áp ngưỡng riêng).
 */
final class CardSignalsEnricher implements StorefrontEnricher
{
    public function __construct(private readonly Signals $signals) {}

    public function resource(): string
    {
        return 'product_card';
    }

    public function enrich(array $items, string $locale): array
    {
        if (! (bool) $this->signals->setting('show_list_label', true)) {
            return [];
        }
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['id']] = array_map('intval', (array) ($item['variant_ids'] ?? []));
        }
        $threshold = $this->signals->threshold([]);
        $result = [];
        foreach ($this->signals->totals($groups) as $id => $total) {
            if ($total > 0 && $total <= $threshold) {
                $result[$id] = ['low_stock' => true];
            }
        }

        return $result;
    }
}
