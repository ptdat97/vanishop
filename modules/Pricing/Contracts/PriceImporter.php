<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

/**
 * Service contract (0.3.27): đặt giá niêm yết (bảng giá `base`) theo SKU từ nguồn ngoài (plugin dữ liệu demo, ERP). Bảng
 * `base` chưa có thì tạo. SKU không tồn tại → bỏ qua và trả về trong `unknown`. Có audit + event PriceChanged.
 */
interface PriceImporter
{
    /**
     * @param  array<string, array{amount: int, compare_at?: int|null}>  $prices  sku => giá (VND)
     * @return array{updated: int, unknown: list<string>}
     */
    public function setBasePrices(array $prices): array;
}
