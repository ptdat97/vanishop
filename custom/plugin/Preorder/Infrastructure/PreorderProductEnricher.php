<?php

declare(strict_types=1);

namespace Plugin\Preorder\Infrastructure;

use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\Backorders;
use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Trang sản phẩm (native + Storefront API): `extensions["vani.preorder"]` = `{variants: {variant id: ngày dự kiến Y-m-d|null}}`
 * cho SKU đã hết hàng thật và còn suất đặt trước (số bán được chỉ còn từ hạn mức).
 */
final class PreorderProductEnricher implements StorefrontEnricher
{
    public function __construct(private readonly Backorders $backorders, private readonly AvailabilityReader $availability) {}

    public function resource(): string
    {
        return 'product';
    }

    public function enrich(array $items, string $locale): array
    {
        $result = [];
        foreach ($items as $product) {
            $variantIds = array_map('intval', array_column((array) ($product['variants'] ?? []), 'id'));
            $status = $variantIds === [] ? [] : $this->backorders->status($variantIds);
            if ($status === []) {
                continue;
            }
            $ats = $this->availability->forVariants(array_keys($status));
            $preorder = [];
            foreach ($status as $variantId => $row) {
                if ($row['remaining'] > 0 && ($ats[$variantId] ?? 0) <= $row['remaining']) {
                    $preorder[$variantId] = $row['expected_on'];
                }
            }
            if ($preorder !== []) {
                $result[$product['id']] = ['variants' => (object) $preorder];
            }
        }

        return $result;
    }
}
